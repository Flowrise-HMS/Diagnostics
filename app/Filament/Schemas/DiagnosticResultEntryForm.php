<?php

namespace Modules\Diagnostics\Filament\Schemas;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Modules\Clinical\Models\RequestItem;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;

/**
 * The one result-entry schema every path (Diagnostics queue, clinical widgets, lab
 * workspace tab, patient "Fulfill service") renders for a diagnostic request item.
 *
 * Structured fields appear when the service has result fields configured; otherwise the
 * operator types findings. Files can always be attached, so an X-ray or an outside-lab
 * report is a first-class result on its own.
 */
class DiagnosticResultEntryForm
{
    /**
     * @return array<int, Component>
     */
    public static function components(RequestItem $item): array
    {
        $service = app(DiagnosticResultService::class);
        $profile = $service->getProfile($item);
        $templateFields = $profile ? $service->getTemplateFields($profile) : collect();

        $schema = [];

        if ($templateFields->isNotEmpty()) {
            $schema[] = Grid::make(2)
                ->schema($templateFields->map(fn (DiagnosticResultTemplateField $field): Field => self::fieldToComponent($field))->all());
        } else {
            $schema[] = Textarea::make('report_conclusion')
                ->label('Findings / Result')
                ->rows(6)
                ->columnSpanFull();
        }

        $schema[] = self::resultFilesUpload();

        $schema[] = Textarea::make('notes')
            ->label('Notes')
            ->rows(2);

        return $schema;
    }

    /**
     * Result files hold patient-identifiable data, so they are written to the private
     * disk and their original names are kept alongside the generated storage paths.
     */
    public static function resultFilesUpload(): FileUpload
    {
        return FileUpload::make('result_files')
            ->label('Attach report files (optional)')
            ->helperText('PDF, images or Word documents, up to 10 MB each.')
            ->multiple()
            ->disk(config('diagnostics.result_files.disk'))
            ->directory(config('diagnostics.result_files.directory'))
            ->visibility('private')
            ->storeFileNamesIn('result_files_names')
            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->maxSize(10240)
            ->previewable()
            ->openable()
            ->downloadable();
    }

    protected static function fieldToComponent(DiagnosticResultTemplateField $field): Field
    {
        $name = "field_{$field->field_key}";

        $component = match ($field->value_type) {
            'numeric' => TextInput::make($name)
                ->numeric()
                ->step('any')
                ->suffix($field->default_units)
                ->helperText(self::normalRangeHint($field)),
            'select' => Select::make($name)
                ->options(self::parseSelectOptions($field)),
            'long_text' => Textarea::make($name)
                ->rows(4)
                ->columnSpanFull(),
            default => TextInput::make($name),
        };

        return $component
            ->label($field->label)
            ->required((bool) $field->is_required);
    }

    public static function normalRangeHint(DiagnosticResultTemplateField $field): ?string
    {
        $low = $field->reference_range_low;
        $high = $field->reference_range_high;

        if ($low === null && $high === null) {
            return null;
        }

        $format = fn (string $value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        $units = '';

        if (filled($field->default_units)) {
            // "4 - 11 10*9/L" reads as one number; bracket units that start with a digit.
            $units = preg_match('/^\d/', (string) $field->default_units) === 1
                ? " ({$field->default_units})"
                : " {$field->default_units}";
        }

        return match (true) {
            $low !== null && $high !== null => 'Normal: '.$format($low).' - '.$format($high).$units,
            $low !== null => 'Normal: >= '.$format($low).$units,
            default => 'Normal: <= '.$format($high).$units,
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function parseSelectOptions(DiagnosticResultTemplateField $field): array
    {
        $options = $field->options;

        if (is_array($options)) {
            $options = array_values(array_filter(array_map('trim', array_map('strval', $options)), 'strlen'));

            return array_combine($options, $options);
        }

        if (is_string($options)) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $options)), 'strlen'));

            return array_combine($parts, $parts);
        }

        return [];
    }
}
