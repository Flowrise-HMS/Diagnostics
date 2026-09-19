<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Diagnostics\Enums\ResultFieldValueType;
use Modules\Diagnostics\Filament\Schemas\DiagnosticResultEntryForm;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticServiceProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('service.name')
                            ->label('Catalog service'),
                        TextEntry::make('service.code')
                            ->label('Service code'),
                        TextEntry::make('service.category.name')
                            ->label('Category')
                            ->placeholder('-'),
                        TextEntry::make('discipline')
                            ->badge(),
                        TextEntry::make('default_specimen_type')
                            ->label('Default specimen')
                            ->placeholder('-'),
                        TextEntry::make('modality')
                            ->placeholder('-'),
                        TextEntry::make('turnaround_time_minutes')
                            ->label('Turnaround')
                            ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : '-'),
                        IconEntry::make('auto_verify_eligible')
                            ->label('Auto-verify normal results')
                            ->boolean(),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('preparation_instructions')
                            ->label('Patient preparation')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Result fields')
                    ->description('Recorded by the operator when results are entered.')
                    ->schema([
                        TextEntry::make('no_result_fields')
                            ->hiddenLabel()
                            ->state('No structured fields: the operator records free-text findings and can attach files.')
                            ->visible(fn (DiagnosticServiceProfile $record): bool => $record->resultFields()->doesntExist()),
                        RepeatableEntry::make('resultFields')
                            ->hiddenLabel()
                            ->visible(fn (DiagnosticServiceProfile $record): bool => $record->resultFields()->exists())
                            ->table([
                                TableColumn::make('Field'),
                                TableColumn::make('Key'),
                                TableColumn::make('Type'),
                                TableColumn::make('Units'),
                                TableColumn::make('Normal range'),
                                TableColumn::make('Required'),
                            ])
                            ->schema([
                                TextEntry::make('label'),
                                TextEntry::make('field_key'),
                                TextEntry::make('value_type')
                                    ->formatStateUsing(fn (?string $state): string => enum_try_from(ResultFieldValueType::class, (string) $state)?->getLabel() ?? (string) $state),
                                TextEntry::make('default_units')
                                    ->placeholder('-'),
                                TextEntry::make('normal_range')
                                    ->state(fn (DiagnosticResultTemplateField $record): ?string => DiagnosticResultEntryForm::normalRangeHint($record))
                                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('Normal: ', '', $state) : '-'),
                                IconEntry::make('is_required')
                                    ->boolean(),
                            ]),
                    ]),
                Section::make('Coding')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('loinc_code')
                            ->label('LOINC code')
                            ->placeholder('-'),
                        TextEntry::make('loinc_display')
                            ->label('LOINC display')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
