<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;

class DiagnosticObservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'observations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                TextInput::make('display')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options(ObservationStatus::class)
                    ->default(ObservationStatus::FINAL)
                    ->required(),
                TextInput::make('value_type')
                    ->default('numeric'),
                TextInput::make('value_numeric')
                    ->numeric()
                    ->step('any'),
                TextInput::make('value_text'),
                TextInput::make('units'),
                TextInput::make('reference_range_min')
                    ->numeric()
                    ->step('any'),
                TextInput::make('reference_range_max')
                    ->numeric()
                    ->step('any'),
                Select::make('abnormal_flag')
                    ->options(AbnormalFlag::class)
                    ->nullable(),
                TextInput::make('interpretation'),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('display')
                    ->searchable(),
                TextColumn::make('value_numeric')
                    ->label('Value')
                    ->formatStateUsing(fn ($record): string => (string) ($record->value_numeric ?? $record->value_text ?? $record->value_coded ?? '-')),
                TextColumn::make('units')
                    ->placeholder('-'),
                TextColumn::make('reference_range_min')
                    ->label('Ref Low')
                    ->placeholder('-'),
                TextColumn::make('reference_range_max')
                    ->label('Ref High')
                    ->placeholder('-'),
                TextColumn::make('abnormal_flag')
                    ->badge()
                    ->placeholder('-'),
                IconColumn::make('is_critical')
                    ->label('Critical')
                    ->boolean()
                    ->state(fn ($record): bool => in_array($record->abnormal_flag, [AbnormalFlag::CRITICALLY_HIGH, AbnormalFlag::CRITICALLY_LOW], true)),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('finalize_diagnostic_result') ?? false)
                    ->mutateDataUsing(function (array $data): array {
                        $data['performed_by'] = auth()->id();
                        $data['performed_at'] = now();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('finalize_diagnostic_result') ?? false),
                DeleteAction::make(),
            ]);
    }
}
