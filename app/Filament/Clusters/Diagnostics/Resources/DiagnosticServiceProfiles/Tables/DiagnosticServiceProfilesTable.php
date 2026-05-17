<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiagnosticServiceProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('#')->rowIndex(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service.code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discipline')
                    ->badge(),
                TextColumn::make('loinc_code')
                    ->label('LOINC')
                    ->placeholder('-'),
                TextColumn::make('defaultTemplate.name')
                    ->label('Default Template')
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('discipline')
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
