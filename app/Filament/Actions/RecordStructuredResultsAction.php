<?php

namespace Modules\Diagnostics\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Filament\Schemas\DiagnosticResultEntryForm;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class RecordStructuredResultsAction
{
    /**
     * Results can be recorded until the fulfillment is completed; amendments go through
     * the report workflow instead.
     *
     * @return list<FulfillmentStatus>
     */
    public static function recordableStatuses(): array
    {
        return [
            FulfillmentStatus::PENDING,
            FulfillmentStatus::SCHEDULED,
            FulfillmentStatus::COLLECTED,
            FulfillmentStatus::IN_PROGRESS,
        ];
    }

    /**
     * @param  (Closure(): DiagnosticFulfillment)|null  $resolveRecord  Required for page header actions; omit for table row actions.
     */
    public static function make(?Closure $resolveRecord = null): Action
    {
        return Action::make('recordStructuredResults')
            ->label('Record results')
            ->modalHeading(function (?DiagnosticFulfillment $record = null) use ($resolveRecord): string {
                $serviceName = ($record ?? $resolveRecord?->__invoke())?->requestItem?->service?->name;

                return filled($serviceName) ? "Record results - {$serviceName}" : 'Record results';
            })
            ->modalSubmitActionLabel('Submit results')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('primary')
            ->visible(function (?DiagnosticFulfillment $record = null) use ($resolveRecord): bool {
                $user = auth()->user();

                if ($user === null || ! app_settings()->diagnosticsWorkspaceEntryEnabled()) {
                    return false;
                }

                $record ??= $resolveRecord?->__invoke();

                if (! $record instanceof DiagnosticFulfillment || $record->requestItem === null) {
                    return false;
                }

                if (! in_array($record->status, self::recordableStatuses(), true)) {
                    return false;
                }

                return $user->can('recordStructuredResults', $record);
            })
            ->schema(function (?DiagnosticFulfillment $record = null) use ($resolveRecord): array {
                $record ??= $resolveRecord?->__invoke();

                if (! $record instanceof DiagnosticFulfillment || $record->requestItem === null) {
                    return [];
                }

                return DiagnosticResultEntryForm::components($record->requestItem);
            })
            ->action(function (Action $action, array $data, DiagnosticResultService $resultService, ?DiagnosticFulfillment $record = null) use ($resolveRecord): void {
                $record ??= $resolveRecord?->__invoke();

                if (! $record instanceof DiagnosticFulfillment || $record->requestItem === null) {
                    return;
                }

                try {
                    $resultService->submit($record->requestItem, $data);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title('Results were not recorded')
                        ->body($exception->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    $action->halt();
                }

                Notification::make()
                    ->title('Results recorded.')
                    ->success()
                    ->send();
            });
    }
}
