<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Modules\Clinical\Enums\TaskStatus;
use Modules\Clinical\Models\Task;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticResultFile;

class DiagnosticFulfillmentInfolist
{
    /**
     * @var array<string, Task|null>
     */
    protected static array $completedTaskCache = [];

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

                Section::make('Submitted Results')
                    ->description('Values recorded when the result was submitted.')
                    ->visible(fn (DiagnosticFulfillment $record): bool => $record->observations()->exists())
                    ->schema([
                        RepeatableEntry::make('observations')
                            ->hiddenLabel()
                            ->state(fn (DiagnosticFulfillment $record): Collection => $record->observations()
                                ->with('performedBy')
                                ->orderBy('sort_order')
                                ->get())
                            ->columns(4)
                            ->schema([
                                TextEntry::make('display')
                                    ->label('Test')
                                    ->weight(FontWeight::SemiBold)
                                    ->helperText(fn (DiagnosticObservation $record): ?string => $record->code),
                                TextEntry::make('value_display')
                                    ->label('Result')
                                    ->weight(FontWeight::Bold)
                                    ->color(fn (DiagnosticObservation $record): string => $record->isCritical() ? 'danger' : 'gray')
                                    ->placeholder('-'),
                                TextEntry::make('reference_range_display')
                                    ->label('Reference range')
                                    ->placeholder('-'),
                                TextEntry::make('abnormal_flag')
                                    ->label('Flag')
                                    ->badge()
                                    ->placeholder('-'),
                                TextEntry::make('interpretation')
                                    ->placeholder('-'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('performedBy.name')
                                    ->label('Performed by')
                                    ->placeholder('-'),
                                TextEntry::make('performed_at')
                                    ->label('Performed at')
                                    ->dateTime()
                                    ->placeholder('-'),
                                TextEntry::make('notes')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Result Files')
                    ->description('Files uploaded with the result.')
                    ->visible(fn (DiagnosticFulfillment $record): bool => $record->resultFiles()->exists())
                    ->schema([
                        RepeatableEntry::make('resultFiles')
                            ->hiddenLabel()
                            ->state(fn (DiagnosticFulfillment $record): Collection => $record->resultFiles()
                                ->with('uploadedBy')
                                ->latest()
                                ->get())
                            ->columns(4)
                            ->schema([
                                TextEntry::make('file_name')
                                    ->label('File')
                                    ->icon('heroicon-m-arrow-down-tray')
                                    ->color('primary')
                                    ->url(fn (DiagnosticResultFile $record): ?string => $record->downloadUrl())
                                    ->openUrlInNewTab(),
                                TextEntry::make('file_type')
                                    ->label('Type')
                                    ->placeholder('-'),
                                TextEntry::make('file_size')
                                    ->label('Size')
                                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '-'),
                                TextEntry::make('uploadedBy.name')
                                    ->label('Uploaded by')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Result Entry')
                    ->columns(4)
                    ->visible(fn (DiagnosticFulfillment $record): bool => self::latestCompletedTask($record) !== null)
                    ->schema([
                        TextEntry::make('entry_performed_by')
                            ->label('Submitted by')
                            ->state(fn (DiagnosticFulfillment $record): ?string => self::latestCompletedTask($record)?->performedBy?->name)
                            ->placeholder('-'),
                        TextEntry::make('entry_started_at')
                            ->label('Started at')
                            ->state(fn (DiagnosticFulfillment $record): ?string => self::latestCompletedTask($record)?->started_at?->format('Y-m-d H:i'))
                            ->placeholder('-'),
                        TextEntry::make('entry_completed_at')
                            ->label('Completed at')
                            ->state(fn (DiagnosticFulfillment $record): ?string => self::latestCompletedTask($record)?->completed_at?->format('Y-m-d H:i'))
                            ->placeholder('-'),
                        TextEntry::make('entry_notes')
                            ->label('Notes')
                            ->state(fn (DiagnosticFulfillment $record): ?string => self::latestCompletedTask($record)?->notes)
                            ->placeholder('No notes recorded')
                            ->columnSpanFull(),
                    ]),

                Section::make('Latest Report')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('latestReportVersion.version')
                            ->label('Latest Version')
                            ->placeholder('-'),
                        TextEntry::make('latestReportVersion.title')
                            ->label('Report Title')
                            ->placeholder('-'),
                        TextEntry::make('latestReportVersion.conclusion')
                            ->label('Conclusion')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('latestReportVersion.performedBy.name')
                            ->label('Reported by')
                            ->placeholder('-'),
                        TextEntry::make('latestReportVersion.verifiedBy.name')
                            ->label('Verified by')
                            ->placeholder('-'),
                        TextEntry::make('latestReportVersion.verified_at')
                            ->label('Verified at')
                            ->dateTime()
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

    /**
     * The completed task holds what the operator typed on submission: timings and free-text notes.
     * Resolved once per fulfillment because several entries read from it.
     */
    protected static function latestCompletedTask(DiagnosticFulfillment $fulfillment): ?Task
    {
        $key = (string) $fulfillment->getKey();

        if (array_key_exists($key, self::$completedTaskCache)) {
            return self::$completedTaskCache[$key];
        }

        $fulfillment->loadMissing('requestItem');

        return self::$completedTaskCache[$key] = $fulfillment->requestItem
            ?->tasks()
            ->where('status', TaskStatus::COMPLETED)
            ->with('performedBy')
            ->latest('completed_at')
            ->first();
    }
}
