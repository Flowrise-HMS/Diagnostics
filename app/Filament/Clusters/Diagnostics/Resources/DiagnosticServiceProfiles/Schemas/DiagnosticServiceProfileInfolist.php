<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiagnosticServiceProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('service.name')
                            ->label('Service'),
                        TextEntry::make('service.code')
                            ->label('Service Code'),
                        TextEntry::make('discipline')
                            ->badge(),
                        TextEntry::make('is_active')
                            ->label('Active')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('loinc_code')
                            ->label('LOINC Code')
                            ->placeholder('-'),
                        TextEntry::make('loinc_display')
                            ->label('LOINC Display')
                            ->placeholder('-'),
                    ]),
                Section::make('Templates')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('defaultTemplate.name')
                            ->label('Default Template')
                            ->placeholder('-'),
                        TextEntry::make('templates_count')
                            ->label('Template Count')
                            ->state(fn ($record): int => $record->templates()->count()),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        KeyValueEntry::make('metadata'),
                    ]),
            ]);
    }
}
