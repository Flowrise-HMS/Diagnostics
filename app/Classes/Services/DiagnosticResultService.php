<?php

namespace Modules\Diagnostics\Classes\Services;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Clinical\Enums\TaskOutcome;
use Modules\Clinical\Enums\TaskStatus;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\Task;
use Modules\Core\Classes\Services\BranchService;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticResultService
{
    public function __construct(
        protected BranchService $branchService
    ) {}

    public function getFormSchema(RequestItem $item): array
    {
        $profile = $this->getProfile($item);
        $templateFields = $profile ? $this->getTemplateFields($profile) : collect();

        $schema = [];


        if ($templateFields->isNotEmpty()) {
            foreach ($templateFields as $field) {
                $schema[] = $this->fieldToComponent($field);
            }
        } else {
            $schema[] = Repeater::make('results')
                ->label('Results')
                ->schema([
                    TextInput::make('key')->label('Field')->required(),
                    TextInput::make('value')->label('Value')->required(),
                ])
                ->columns(2)
                ->defaultItems(0);
        }

        $schema[] = FileUpload::make('result_files')
            ->label('Result Files (PDF, Images)')
            ->multiple()
            ->directory('diagnostics/results')
            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->maxSize(10240);

        $schema[] = Textarea::make('notes')
            ->label('Notes')
            ->rows(2);

        return $schema;
    }

    public function getContextInfo(RequestItem $item): array
    {
        $serviceRequest = $item->serviceRequest;
        $service = $item->service;
        $orderedBy = $serviceRequest?->orderedBy;

        return [
            'service_name' => $service?->name ?? 'Unknown',
            'category_name' => $service?->category?->name ?? '-',
            'ordered_by' => $orderedBy?->name ?? 'N/A',
            'ordered_at' => $serviceRequest?->created_at?->format('Y-m-d H:i') ?? 'N/A',
            'priority' => $serviceRequest?->priority?->getLabel() ?? '-',
            'status' => $item->status?->getLabel() ?? '-',
        ];
    }

    public function submit(RequestItem $item, array $data, ?User $user = null): void
    {
        $user = $user ?? Auth::user();

        DB::transaction(function () use ($item, $data, $user) {
            $fulfillment = $this->getOrCreateFulfillment($item);

            $fulfillment->startProcessing();

            $task = $item->tasks()->create([
                'status' => TaskStatus::COMPLETED,
                'performed_by' => $user->id,
                'started_at' => $data['started_at'] ?? now(),
                'completed_at' => $data['ended_at'] ?? now(),
                'results' => $this->buildResultsArray($item, $data),
                'notes' => $data['notes'] ?? null,
                'outcome' => TaskOutcome::COMPLETED,
            ]);

            if ($task->started_at && $task->completed_at) {
                $start = $task->started_at instanceof \Carbon\Carbon
                    ? $task->started_at
                    : \Carbon\Carbon::parse($task->started_at);
                $end = $task->completed_at instanceof \Carbon\Carbon
                    ? $task->completed_at
                    : \Carbon\Carbon::parse($task->completed_at);
                $task->update(['duration_minutes' => $start->diffInMinutes($end)]);
            }

            if (! empty($data['result_files'])) {
                foreach ((array) $data['result_files'] as $file) {
                    $fileModel = $fulfillment->resultFiles()->create([
                        'branch_id' => $this->branchService->getDefaultBranchId(),
                        'file_name' => $file instanceof \Illuminate\Http\UploadedFile
                            ? $file->getClientOriginalName()
                            : (is_string($file) ? basename($file) : 'file'),
                        'file_path' => $file instanceof \Illuminate\Http\UploadedFile
                            ? $file->store('diagnostics/results', 'public')
                            : (is_string($file) ? $file : null),
                        'mime_type' => $file instanceof \Illuminate\Http\UploadedFile
                            ? $file->getMimeType()
                            : null,
                        'file_type' => $file instanceof \Illuminate\Http\UploadedFile
                            ? $file->getClientOriginalExtension()
                            : null,
                        'source' => 'internal_entry',
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            $item->markAsFulfilled($user->id);

            $fulfillment->finalizeResult();
        });
    }

    public function getProfile(RequestItem $item): ?DiagnosticServiceProfile
    {
        return DiagnosticServiceProfile::query()
            ->where('service_id', $item->service_id)
            ->where('is_active', true)
            ->with('defaultTemplate.fields')
            ->first();
    }

    public function getTemplateFields(DiagnosticServiceProfile $profile): Collection
    {
        $template = $profile->defaultTemplate;
        if (! $template) {
            $template = $profile->templates()->where('is_active', true)->first();
        }

        return $template?->fields ?? collect();
    }

    protected function fieldToComponent(DiagnosticResultTemplateField $field): TextInput|Select
    {
        $name = "field_{$field->field_key}";

        return match ($field->value_type) {
            'numeric' => TextInput::make($name)
                ->label($field->label)
                ->numeric()
                ->step('any'),
            'select' => Select::make($name)
                ->label($field->label)
                ->options($this->parseSelectOptions($field)),
            default => TextInput::make($name)
                ->label($field->label),
        };
    }

    protected function parseSelectOptions(DiagnosticResultTemplateField $field): array
    {
        $options = $field->options;

        if (is_array($options)) {
            return array_combine($options, $options);
        }

        if (is_string($options)) {
            $parts = explode(',', $options);
            $parts = array_map('trim', $parts);
            return array_combine($parts, $parts);
        }

        return [];
    }

    protected function buildResultsArray(RequestItem $item, array $data): array
    {
        $profile = $this->getProfile($item);
        $templateFields = $profile ? $this->getTemplateFields($profile) : collect();
        $results = [];

        if ($templateFields->isNotEmpty()) {
            foreach ($templateFields as $field) {
                $name = "field_{$field->field_key}";
                if (array_key_exists($name, $data)) {
                    $results[$field->field_key] = [
                        'label' => $field->label,
                        'value' => $data[$name],
                        'type' => $field->value_type,
                    ];
                }
            }
        } elseif (! empty($data['results'])) {
            foreach ($data['results'] as $row) {
                if (! empty($row['key'])) {
                    $results[$row['key']] = $row['value'];
                }
            }
        }

        return $results;
    }

    protected function getOrCreateFulfillment(RequestItem $item): DiagnosticFulfillment
    {
        return DiagnosticFulfillment::query()->firstOrCreate(
            ['request_item_id' => $item->id],
            [
                'branch_id' => $this->branchService->getDefaultBranchId(),
                'discipline' => 'lab',
                'status' => FulfillmentStatus::PENDING,
            ]
        );
    }
}
