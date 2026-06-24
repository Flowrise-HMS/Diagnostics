<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiagnosticReferenceRangesRelationManager extends RelationManager
{
    protected static string $relationship = 'referenceRanges';

    protected static ?string $title = 'Reference Ranges';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('gender')
                    ->options([
                        'any' => 'Any',
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                    ])
                    ->default('any')
                    ->required(),
                TextInput::make('age_min_months')
                    ->label('Age Min (months)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('age_max_months')
                    ->label('Age Max (months)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('min_value')
                    ->label('Normal Low')
                    ->numeric(),
                TextInput::make('max_value')
                    ->label('Normal High')
                    ->numeric(),
                TextInput::make('units')
                    ->maxLength(50),
                TextInput::make('critical_low')
                    ->label('Critical Low')
                    ->numeric(),
                TextInput::make('critical_high')
                    ->label('Critical High')
                    ->numeric(),
                Textarea::make('range_text')
                    ->label('Range Text')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gender')
                    ->badge(),
                TextColumn::make('age_min_months')
                    ->label('Age Min')
                    ->placeholder('—'),
                TextColumn::make('age_max_months')
                    ->label('Age Max')
                    ->placeholder('—'),
                TextColumn::make('min_value')
                    ->label('Low')
                    ->placeholder('—'),
                TextColumn::make('max_value')
                    ->label('High')
                    ->placeholder('—'),
                TextColumn::make('units')
                    ->placeholder('—'),
                TextColumn::make('critical_low')
                    ->label('Crit. Low')
                    ->placeholder('—'),
                TextColumn::make('critical_high')
                    ->label('Crit. High')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageReferenceRanges', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageReferenceRanges', $this->getOwnerRecord()) ?? false),
                DeleteAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageReferenceRanges', $this->getOwnerRecord()) ?? false),
            ]);
    }
}
