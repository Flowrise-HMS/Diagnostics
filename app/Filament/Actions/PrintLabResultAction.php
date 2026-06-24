<?php

namespace Modules\Diagnostics\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Modules\Diagnostics\Classes\Services\DiagnosticLabResultPrintService;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class PrintLabResultAction
{
    /**
     * @param  (Closure(): DiagnosticFulfillment)|null  $resolveRecord  Required for page header actions; omit for table row actions.
     */
    public static function make(?Closure $resolveRecord = null): Action
    {
        $printService = app(DiagnosticLabResultPrintService::class);

        return Action::make('printLabResult')
            ->label('Print Lab Result')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(function (mixed ...$args) use ($resolveRecord): string {
                $record = $resolveRecord !== null
                    ? $resolveRecord()
                    : ($args[0] ?? null);

                return route('diagnostics.fulfillments.lab-result.print', [
                    'fulfillment' => $record,
                    'auto' => 1,
                ]);
            })
            ->openUrlInNewTab()
            ->visible(function (mixed ...$args) use ($resolveRecord, $printService): bool {
                $user = auth()->user();

                if ($user === null) {
                    return false;
                }

                $record = $resolveRecord !== null
                    ? $resolveRecord()
                    : ($args[0] ?? null);

                if (! $record instanceof DiagnosticFulfillment) {
                    return false;
                }

                return $user->can('printLabResult', $record)
                    && $printService->canPrint($record);
            });
    }
}
