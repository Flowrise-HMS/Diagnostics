<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiagnosticResultTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('#')->rowIndex(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('profile.service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('profile.discipline')
                    ->label('Discipline')
                    ->badge(),
                TextColumn::make('fields_count')
                    ->label('Fields')
                    ->state(fn ($record): int => $record->fields()->count()),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('profile.discipline')
                    ->label('Discipline')
                    ->options([
                        'lab' => 'Lab',
                        'radiology' => 'Radiology',
                        'pathology' => 'Pathology',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
