<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Enums\ReportVersionStatus;
use Modules\Diagnostics\Filament\Actions\PrintLabResultAction;
use Modules\Diagnostics\Filament\Actions\RecordStructuredResultsAction;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Core\Filament\Support\ClientIdentityColumn;

class DiagnosticFulfillmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('#')->rowIndex(),
                TextColumn::make('requestItem.serviceRequest.request_number')
                    ->label('Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('requestItem.service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                ClientIdentityColumn::make(
                    resolve: fn (DiagnosticFulfillment $record) => $record->requestItem?->serviceRequest?->clientIdentity(),
                ),
                TextColumn::make('accession_number')
                    ->label('Accession')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('discipline')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('latestReportVersion.is_critical')
                    ->label('Critical')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('specimens_count')
                    ->label('Specimens')
                    ->state(fn (DiagnosticFulfillment $record): int => $record->specimens()->count()),
                TextColumn::make('report_versions_count')
                    ->label('Reports')
                    ->state(fn (DiagnosticFulfillment $record): int => $record->reportVersions()->count()),
                TextColumn::make('result_files_count')
                    ->label('Files')
                    ->state(fn (DiagnosticFulfillment $record): int => $record->resultFiles()->count()),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(FulfillmentStatus::class),
                SelectFilter::make('discipline')
                    ->options(DiagnosticDiscipline::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('schedule')
                        ->label('Schedule')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('assign', $record)
                            && $record->discipline->supportsSchedulingWorkflow()
                            && $record->status === FulfillmentStatus::PENDING)
                        ->schema([
                            DateTimePicker::make('scheduled_at')
                                ->label('Scheduled At')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (DiagnosticFulfillment $record, array $data): void {
                            $record->schedule($data['scheduled_at']);

                            Notification::make()
                                ->title('Fulfillment scheduled.')
                                ->success()
                                ->send();
                        }),
                    Action::make('collectSpecimen')
                        ->label('Collect Specimen')
                        ->icon('heroicon-o-beaker')
                        ->color('warning')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('collectSpecimen', $record)
                            && $record->discipline->supportsSpecimenWorkflow()
                            && in_array($record->status, [FulfillmentStatus::PENDING, FulfillmentStatus::SCHEDULED], true))
                        ->schema([
                            Select::make('specimen_type')
                                ->options([
                                    'blood' => 'Blood',
                                    'urine' => 'Urine',
                                    'swab' => 'Swab',
                                    'tissue' => 'Tissue',
                                ])
                                ->required(),
                        ])
                        ->action(function (DiagnosticFulfillment $record, array $data): void {
                            $record->collectSpecimen($data['specimen_type'], auth()->user());

                            Notification::make()
                                ->title('Specimen collected.')
                                ->success()
                                ->send();
                        }),
                    Action::make('startProcessing')
                        ->label('Start Processing')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('startProcessing', $record) && in_array($record->status, [FulfillmentStatus::PENDING, FulfillmentStatus::SCHEDULED, FulfillmentStatus::COLLECTED], true))
                        ->requiresConfirmation()
                        ->action(function (DiagnosticFulfillment $record): void {
                            $record->startProcessing();

                            Notification::make()
                                ->title('Fulfillment moved to in progress.')
                                ->success()
                                ->send();
                        }),
                    Action::make('finalizeResult')
                        ->label('Finalize Result')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('finalizeResult', $record))
                        ->schema([
                            Select::make('report_status')
                                ->options(ReportVersionStatus::class)
                                ->default(ReportVersionStatus::FINAL)
                                ->required(),
                        ])
                        ->action(function (DiagnosticFulfillment $record, array $data): void {
                            $record->finalizeResult($data['report_status']);

                            Notification::make()
                                ->title('Diagnostic result finalized.')
                                ->success()
                                ->send();
                        }),
                    Action::make('verifyResult')
                        ->label('Verify Result')
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('verifyResult', $record) && $record->latestReportVersion !== null)
                        ->requiresConfirmation()
                        ->action(function (DiagnosticFulfillment $record): void {
                            $record->verifyResult();

                            Notification::make()
                                ->title('Latest report version verified.')
                                ->success()
                                ->send();
                        }),
                    Action::make('signReport')
                        ->label('Sign Report')
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('signReport', $record) && $record->latestReportVersion !== null)
                        ->schema([
                            Select::make('role')
                                ->options([
                                    'reviewer' => 'Reviewer',
                                    'pathologist' => 'Pathologist',
                                    'radiologist' => 'Radiologist',
                                    'laboratory_scientist' => 'Laboratory Scientist',
                                ])
                                ->nullable(),
                            Textarea::make('notes')
                                ->rows(3)
                                ->nullable(),
                        ])
                        ->action(function (DiagnosticFulfillment $record, array $data): void {
                            $record->signReport(auth()->user(), $data['role'] ?? null, $data['notes'] ?? null);

                            Notification::make()
                                ->title('Report signature recorded.')
                                ->success()
                                ->send();
                        }),
                    Action::make('amendReport')
                        ->label('Amend Report')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (DiagnosticFulfillment $record): bool => auth()->user()?->can('amendReport', $record) && $record->latestReportVersion !== null)
                        ->requiresConfirmation()
                        ->action(function (DiagnosticFulfillment $record): void {
                            $record->amendReport();

                            Notification::make()
                                ->title('Amended report version created.')
                                ->success()
                                ->send();
                        }),
                    PrintLabResultAction::make(),
                    RecordStructuredResultsAction::make(),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('activities')
                        ->label('Activities')
                        ->icon('heroicon-o-bell-alert')
                        ->url(fn ($record) => DiagnosticFulfillmentResource::getUrl('activities', ['record' => $record])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
