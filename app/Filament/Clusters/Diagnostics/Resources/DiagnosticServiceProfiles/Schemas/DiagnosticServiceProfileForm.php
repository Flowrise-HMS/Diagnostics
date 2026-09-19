<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\ResultFieldValueType;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

/**
 * One screen describes a diagnostic service end to end: which catalog service it is,
 * how it is performed (discipline, specimen or modality, turnaround) and which result
 * fields the lab or radiographer fills in. The result template behind those fields is
 * managed automatically and never shown.
 */
class DiagnosticServiceProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service')
                    ->columns(2)
                    ->schema([
                        Select::make('service_id')
                            ->label('Catalog service')
                            ->options(fn (?DiagnosticServiceProfile $record): array => self::serviceOptions($record))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                if (blank($state) || filled($get('discipline'))) {
                                    return;
                                }

                                $service = Service::query()->withoutGlobalScope('branch')->with('category')->find($state);
                                $discipline = DiagnosticDiscipline::fromServiceCategoryCode(
                                    DiagnosticCatalogService::categoryCode($service?->category),
                                );

                                if ($discipline !== null) {
                                    $set('discipline', $discipline->value);
                                }
                            })
                            ->helperText('Price and billing are managed on the service itself.'),
                        Select::make('discipline')
                            ->options(DiagnosticDiscipline::class)
                            ->required()
                            ->live(),
                        TextInput::make('default_specimen_type')
                            ->label('Default specimen')
                            ->placeholder('e.g. blood, urine, swab')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => in_array(self::disciplineValue($get), [DiagnosticDiscipline::LAB->value, DiagnosticDiscipline::PATHOLOGY->value], true)),
                        TextInput::make('modality')
                            ->label('Modality')
                            ->placeholder('e.g. XR, US, CT, ECG')
                            ->maxLength(20)
                            ->visible(fn (Get $get): bool => self::disciplineValue($get) === DiagnosticDiscipline::RADIOLOGY->value),
                        TextInput::make('turnaround_time_minutes')
                            ->label('Turnaround (minutes)')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('auto_verify_eligible')
                            ->label('Auto-verify normal results')
                            ->helperText('Results with every value in range are verified without a second review.')
                            ->default(false),
                        Textarea::make('preparation_instructions')
                            ->label('Patient preparation')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                Section::make('Result fields')
                    ->description('What the operator records for this service. Leave empty for a free-text findings box plus file upload (typical for imaging).')
                    ->schema([
                        Repeater::make('result_fields')
                            ->hiddenLabel()
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Add result field')
                            ->defaultItems(0)
                            ->columns(3)
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                        if (blank($get('field_key')) && filled($state)) {
                                            $set('field_key', Str::slug($state, '_'));
                                        }
                                    }),
                                TextInput::make('field_key')
                                    ->label('Key')
                                    ->helperText('Stable identifier, e.g. hemoglobin')
                                    ->required()
                                    ->alphaDash()
                                    ->distinct()
                                    ->maxLength(100),
                                Select::make('value_type')
                                    ->label('Type')
                                    ->options(ResultFieldValueType::class)
                                    ->default(ResultFieldValueType::Numeric->value)
                                    ->required()
                                    ->live(),
                                TextInput::make('default_units')
                                    ->label('Units')
                                    ->maxLength(50)
                                    ->visible(fn (Get $get): bool => self::valueType($get) === ResultFieldValueType::Numeric->value),
                                TextInput::make('reference_range_low')
                                    ->label('Normal low')
                                    ->numeric()
                                    ->visible(fn (Get $get): bool => self::valueType($get) === ResultFieldValueType::Numeric->value),
                                TextInput::make('reference_range_high')
                                    ->label('Normal high')
                                    ->numeric()
                                    ->visible(fn (Get $get): bool => self::valueType($get) === ResultFieldValueType::Numeric->value),
                                TagsInput::make('options')
                                    ->label('Choices')
                                    ->placeholder('Type a choice and press Enter')
                                    ->visible(fn (Get $get): bool => self::valueType($get) === ResultFieldValueType::Select->value)
                                    ->columnSpan(2),
                                Toggle::make('is_required')
                                    ->label('Required')
                                    ->default(false)
                                    ->inline(false),
                                TextInput::make('observation_code')
                                    ->label('LOINC code (optional)')
                                    ->maxLength(50),
                            ]),
                    ]),
                Section::make('Coding')
                    ->description('Optional LOINC coding for interoperability.')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('loinc_code')
                            ->label('LOINC code')
                            ->maxLength(50),
                        TextInput::make('loinc_display')
                            ->label('LOINC display')
                            ->maxLength(255),
                    ]),
            ]);
    }

    protected static function valueType(Get $get): ?string
    {
        $value = $get('value_type');

        return $value instanceof ResultFieldValueType ? $value->value : ($value === null ? null : (string) $value);
    }

    /**
     * The discipline arrives as a string from the select and as an enum from the record.
     */
    protected static function disciplineValue(Get $get): ?string
    {
        $value = $get('discipline');

        return $value instanceof DiagnosticDiscipline ? $value->value : ($value === null ? null : (string) $value);
    }

    /**
     * On create, only services that have no diagnostic setup yet; on edit, the current
     * service so the disabled select still shows its label.
     *
     * @return array<string, string>
     */
    protected static function serviceOptions(?DiagnosticServiceProfile $record): array
    {
        $query = Service::query()
            ->withoutGlobalScope('branch')
            ->nonMedication()
            ->orderBy('name');

        if ($record !== null) {
            $query->whereKey($record->service_id);
        } else {
            $query->whereNotIn('id', DiagnosticServiceProfile::query()->withoutGlobalScope('branch')->select('service_id'));
        }

        return $query->get()
            ->mapWithKeys(fn (Service $service): array => [$service->id => "{$service->name} ({$service->code})"])
            ->all();
    }
}
