<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Schemas;

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
                        TextEntry::make('requestItem.serviceRequest.patient.full_name')
                            ->label('Patient')
                            ->placeholder(fn (DiagnosticFulfillment $record): string => $record->requestItem?->serviceRequest?->guest_name ?? 'Guest'),
                    ]),

                Section::make('Fulfillment')
                    ->columns(2)
                    ->schema([
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
                        TextEntry::make('latestReportVersion.signatures_count')
                            ->label('Signatures')
                            ->state(fn (DiagnosticFulfillment $record): int => $record->latestReportVersion?->signatures()->count() ?? 0),
                    ]),
            ]);
    }
}
