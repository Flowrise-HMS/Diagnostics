<?php

namespace Modules\Diagnostics\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Filament\Schemas\DiagnosticResultEntryForm;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class RecordStructuredResultsAction
{
    /**
     * @param  (Closure(): DiagnosticFulfillment)|null  $resolveRecord  Required for page header actions; omit for table row actions.
     */
    public static function make(?Closure $resolveRecord = null): Action
    {
        return Action::make('recordStructuredResults')
            ->label('Record Structured Results')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('primary')
            ->visible(function (mixed ...$args) use ($resolveRecord): bool {
                $user = auth()->user();

                if ($user === null) {
                    return false;
                }

                $record = $resolveRecord !== null
                    ? $resolveRecord()
                    : ($args[0] ?? null);

                if (! $record instanceof DiagnosticFulfillment || $record->requestItem === null) {
                    return false;
                }

                return $user->can('recordStructuredResults', $record);
            })
            ->schema(function (mixed ...$args) use ($resolveRecord): array {
                $record = $resolveRecord !== null
                    ? $resolveRecord()
                    : ($args[0] ?? null);

                if (! $record instanceof DiagnosticFulfillment || $record->requestItem === null) {
                    return [];
                }

                return DiagnosticResultEntryForm::components($record->requestItem);
            })
            ->action(function (array $data, DiagnosticResultService $resultService, mixed ...$args) use ($resolveRecord): void {
                $record = $resolveRecord !== null
                    ? $resolveRecord()
                    : ($args[0] ?? null);

                if (! $record instanceof DiagnosticFulfillment || $record->requestItem === null) {
                    return;
                }

                $resultService->submit($record->requestItem, $data);

                Notification::make()
                    ->title('Structured results recorded.')
                    ->success()
                    ->send();
            });
    }
}
