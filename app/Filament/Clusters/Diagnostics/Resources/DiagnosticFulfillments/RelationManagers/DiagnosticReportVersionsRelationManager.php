<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiagnosticReportVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'reportVersions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('version')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->options([
                        'preliminary' => 'Preliminary',
                        'final' => 'Final',
                        'amended' => 'Amended',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')
                    ->label('Version'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('signatures_count')
                    ->label('Signatures')
                    ->state(fn ($record): int => $record->signatures()->count()),
                TextColumn::make('result_files_count')
                    ->label('Files')
                    ->state(fn ($record): int => $record->resultFiles()->count()),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('Update DiagnosticFulfillment') ?? false),
            ]);
    }
}
