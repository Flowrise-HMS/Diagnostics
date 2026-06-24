<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticResultTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('profile_id')
                                    ->label('Diagnostic Service Profile')
                                    ->options(fn (): array => DiagnosticServiceProfile::query()
                                        ->with('service')
                                        ->orderBy('discipline')
                                        ->get()
                                        ->mapWithKeys(fn (DiagnosticServiceProfile $profile): array => [
                                            $profile->id => ($profile->service?->name ?? 'Unknown Service').' ('.strtoupper($profile->discipline).')',
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Toggle::make('is_default')
                                    ->default(false),
                                Toggle::make('is_active')
                                    ->default(true),
                            ]),
                    ]),
                Section::make('Fields')
                    ->description('Admin-defined UI fields that pre-load during structured result entry.')
                    ->schema([
                        Repeater::make('fields')
                            ->relationship()
                            ->schema([
                                TextInput::make('field_key')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('observation_code')
                                    ->label('Observation Code (LOINC)')
                                    ->maxLength(255),
                                TextInput::make('observation_name')
                                    ->maxLength(255),
                                Select::make('value_type')
                                    ->options([
                                        'numeric' => 'Numeric',
                                        'text' => 'Text',
                                        'select' => 'Select',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('default_units')
                                    ->label('Default Units')
                                    ->maxLength(50),
                                Toggle::make('is_required')
                                    ->default(false),
                                TextInput::make('reference_range_low')
                                    ->label('Reference Low')
                                    ->numeric(),
                                TextInput::make('reference_range_high')
                                    ->label('Reference High')
                                    ->numeric(),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                TextInput::make('options')
                                    ->label('Select Options (comma-separated)')
                                    ->visible(fn ($get) => $get('value_type') === 'select')
                                    ->placeholder('Option 1, Option 2, Option 3'),
                            ])
                            ->columns(2)
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
