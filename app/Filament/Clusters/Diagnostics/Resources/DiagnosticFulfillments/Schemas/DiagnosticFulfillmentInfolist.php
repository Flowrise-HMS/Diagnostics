<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticFulfillmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('requestItem.serviceRequest.request_number')
                            ->label('Request Number'),
                        TextEntry::make('requestItem.service.name')
                            ->label('Service'),
                        TextEntry::make('requestItem.service.code')
                            ->label('Service Code'),
                        TextEntry::make('client')
                            ->label(__('Client'))
                            ->state(fn (DiagnosticFulfillment $record): string => $record->requestItem?->serviceRequest?->clientIdentity()->displayWithIdentifier() ?? 'N/A'),
                    ]),

                Section::make('Fulfillment')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('accession_number')
                            ->label('Accession Number')
                            ->placeholder('-'),
                        TextEntry::make('priority')
                            ->badge(),
                        TextEntry::make('scheduled_at')
                            ->label('Scheduled At')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('discipline')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('branch.name')
                            ->label('Branch'),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ]),

                Section::make('Related Records')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('specimens_count')
                            ->label('Specimens')
                            ->state(fn (DiagnosticFulfillment $record): int => $record->specimens()->count()),
                        TextEntry::make('observations_count')
                            ->label('Observations')
                            ->state(fn (DiagnosticFulfillment $record): int => $record->observations()->count()),
                        TextEntry::make('report_versions_count')
                            ->label('Reports')
                            ->state(fn (DiagnosticFulfillment $record): int => $record->reportVersions()->count()),
                        TextEntry::make('result_files_count')
                            ->label('Files')
                            ->state(fn (DiagnosticFulfillment $record): int => $record->resultFiles()->count()),
                    ]),

                Section::make('Latest Report')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('latestReportVersion.version')
                            ->label('Latest Version')
                            ->placeholder('-'),
                        TextEntry::make('latestReportVersion.status')
                            ->label('Report Status')
                            ->badge()
                            ->placeholder('-'),
                        IconEntry::make('latestReportVersion.is_critical')
                            ->label('Critical Result')
                            ->boolean()
                            ->trueIcon('heroicon-o-exclamation-triangle')
                            ->falseIcon('heroicon-o-check-circle')
                            ->trueColor('danger')
                            ->falseColor('success'),
                        TextEntry::make('latestReportVersion.critical_notified_at')
                            ->label('Critical Notified At')
                            ->dateTime()
                            ->placeholder('-')
                            ->visible(fn (DiagnosticFulfillment $record): bool => (bool) $record->latestReportVersion?->is_critical),
                        TextEntry::make('latestReportVersion.signatures_count')
                            ->label('Signatures')
                            ->state(fn (DiagnosticFulfillment $record): int => $record->latestReportVersion?->signatures()->count() ?? 0),
                    ]),
            ]);
    }
}
