<?php

namespace Modules\Diagnostics\Classes\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Clinical\Enums\TaskOutcome;
use Modules\Clinical\Enums\TaskStatus;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Classes\Services\BranchService;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FileSourceType;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Filament\Schemas\DiagnosticResultEntryForm;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticResultService
{
    public function __construct(
        protected BranchService $branchService,
        protected DiagnosticObservationWriter $observationWriter,
    ) {}

    public function getFormSchema(RequestItem $item): array
    {
        return DiagnosticResultEntryForm::components($item);
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
            $profile = $this->getProfile($item);

            $fulfillment->startProcessing();

            $reportVersion = $fulfillment->finalizeResult(
                $data['report_status'] ?? 'final',
                $this->buildReportVersionAttributes($profile, $data, $user),
            );

            $specimen = $fulfillment->specimens()->latest('collected_at')->first();

            $observations = $profile
                ? $this->observationWriter->persistResults(
                    fulfillment: $fulfillment,
                    profile: $profile,
                    formData: $data,
                    reportVersion: $reportVersion,
                    specimen: $specimen,
                    performedBy: $user,
                )
                : collect();

            if ($profile && $this->shouldAutoVerify($profile, $observations)) {
                $fulfillment->verifyResult($user);
            }

            $task = $item->tasks()->create([
                'status' => TaskStatus::COMPLETED,
                'performed_by' => $user->id,
                'started_at' => $data['started_at'] ?? now(),
                'completed_at' => $data['ended_at'] ?? now(),
                'results' => $this->buildResultsSummary($observations, $item, $data),
                'notes' => $data['notes'] ?? null,
                'outcome' => TaskOutcome::COMPLETED,
            ]);

            if ($task->started_at && $task->completed_at) {
                $start = $task->started_at instanceof Carbon
                    ? $task->started_at
                    : Carbon::parse($task->started_at);
                $end = $task->completed_at instanceof Carbon
                    ? $task->completed_at
                    : Carbon::parse($task->completed_at);
                $task->update(['duration_minutes' => $start->diffInMinutes($end)]);
            }

            if (! empty($data['result_files'])) {
                foreach ((array) $data['result_files'] as $file) {
                    $fulfillment->resultFiles()->create([
                        'branch_id' => $this->branchService->getDefaultBranchId(),
                        'report_version_id' => $reportVersion->id,
                        'file_name' => $file instanceof UploadedFile
                            ? $file->getClientOriginalName()
                            : (is_string($file) ? basename($file) : 'file'),
                        'file_path' => $file instanceof UploadedFile
                            ? $file->store('diagnostics/results', 'public')
                            : (is_string($file) ? $file : null),
                        'mime_type' => $file instanceof UploadedFile
                            ? $file->getMimeType()
                            : null,
                        'file_type' => $file instanceof UploadedFile
                            ? $file->getClientOriginalExtension()
                            : null,
                        'source' => FileSourceType::INTERNAL_ENTRY,
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            $item->markAsFulfilled($user->id);
        });
    }

    public function getProfile(RequestItem $item): ?DiagnosticServiceProfile
    {
        return DiagnosticServiceProfile::query()
            ->where('service_id', $item->service_id)
            ->where('is_active', true)
            ->with(['defaultTemplate.fields', 'panel.items.childProfile'])
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

    /**
     * @param  Collection<int, DiagnosticObservation>  $observations
     * @return array<string, mixed>
     */
    protected function buildResultsSummary(Collection $observations, RequestItem $item, array $data): array
    {
        if ($observations->isNotEmpty()) {
            return $observations->mapWithKeys(fn (DiagnosticObservation $observation): array => [
                $observation->code => [
                    'value' => $observation->value_numeric ?? $observation->value_text ?? $observation->value_coded,
                    'abnormal_flag' => $observation->abnormal_flag?->value,
                ],
            ])->all();
        }

        return $this->buildResultsArray($item, $data);
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
        $item->loadMissing('serviceRequest');

        $profile = $this->getProfile($item);

        return DiagnosticFulfillment::query()->firstOrCreate(
            ['request_item_id' => $item->id],
            [
                'branch_id' => $item->serviceRequest?->branch_id ?? $this->branchService->getDefaultBranchId(),
                'discipline' => $profile?->discipline ?? DiagnosticDiscipline::LAB,
                'status' => FulfillmentStatus::PENDING,
            ]
        );
    }

    /**
     * @param  Collection<int, DiagnosticObservation>  $observations
     */
    protected function shouldAutoVerify(DiagnosticServiceProfile $profile, Collection $observations): bool
    {
        if (! $profile->auto_verify_eligible || $observations->isEmpty()) {
            return false;
        }

        return $observations->every(function (DiagnosticObservation $observation): bool {
            if ($observation->abnormal_flag === null) {
                return true;
            }

            return in_array($observation->abnormal_flag, [AbnormalFlag::NORMAL, AbnormalFlag::NEGATIVE], true);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildReportVersionAttributes(
        ?DiagnosticServiceProfile $profile,
        array $data,
        User $user,
    ): array {
        $attributes = [
            'performed_by' => $user->id,
        ];

        if (! empty($data['report_conclusion'])) {
            $attributes['conclusion'] = $data['report_conclusion'];
        } elseif ($profile?->discipline === DiagnosticDiscipline::PATHOLOGY) {
            $gross = $data['gross_description'] ?? $data['field_gross_description'] ?? null;
            $microscopic = $data['microscopic_description'] ?? $data['field_microscopic_description'] ?? null;
            $diagnosis = $data['diagnosis'] ?? $data['field_diagnosis'] ?? null;

            $parts = array_filter([
                filled($gross) ? 'Gross: '.$gross : null,
                filled($microscopic) ? 'Microscopic: '.$microscopic : null,
                filled($diagnosis) ? 'Diagnosis: '.$diagnosis : null,
            ]);

            if ($parts !== []) {
                $attributes['conclusion'] = implode("\n\n", $parts);
            }
        }

        if (! empty($data['report_title'])) {
            $attributes['title'] = $data['report_title'];
        }

        if (! empty($data['conclusion_codes']) && is_array($data['conclusion_codes'])) {
            $attributes['conclusion_codes'] = $data['conclusion_codes'];
        }

        return $attributes;
    }
}
