<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiagnosticResultTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('profile.service.name')
                            ->label('Service'),
                        TextEntry::make('profile.discipline')
                            ->label('Discipline')
                            ->badge(),
                        TextEntry::make('is_default')
                            ->label('Default')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('is_active')
                            ->label('Active')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                    ]),
                Section::make('Fields')
                    ->schema([
                        TextEntry::make('fields_summary')
                            ->label('Field Summary')
                            ->state(fn ($record): string => $record->fields
                                ->sortBy('sort_order')
                                ->map(fn ($field): string => "{$field->sort_order}. {$field->label} [{$field->value_type}]")
                                ->implode("\n")),
                    ]),
            ]);
    }
}
