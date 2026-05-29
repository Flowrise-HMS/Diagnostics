<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Models\Service;

class DiagnosticServiceProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('service_id')
                                    ->label('Service')
                                    ->options(fn (): array => Service::query()->nonMedication()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Service $service): array => [
                                            $service->id => "{$service->name} ({$service->code})",
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('discipline')
                                    ->options([
                                        'lab' => 'Lab',
                                        'radiology' => 'Radiology',
                                        'pathology' => 'Pathology',
                                    ])
                                    ->required(),
                                TextInput::make('loinc_code')
                                    ->label('LOINC Code'),
                                TextInput::make('loinc_display')
                                    ->label('LOINC Display'),
                                Toggle::make('is_active')
                                    ->default(true),
                            ]),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Metadata')
                            ->reorderable(),
                    ]),
            ]);
    }
}
