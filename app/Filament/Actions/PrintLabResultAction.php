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
            ->url(function (?DiagnosticFulfillment $record = null) use ($resolveRecord): string {
                $record ??= $resolveRecord?->__invoke();

                return route('diagnostics.fulfillments.lab-result.print', [
                    'fulfillment' => $record,
                    'auto' => 1,
                ]);
            })
            ->openUrlInNewTab()
            ->visible(function (?DiagnosticFulfillment $record = null) use ($resolveRecord, $printService): bool {
                $user = auth()->user();

                if ($user === null) {
                    return false;
                }

                $record ??= $resolveRecord?->__invoke();

                if ($record === null) {
                    return false;
                }

                if (! $user->can('printLabResult', $record)) {
                    return false;
                }

                return $printService->canPrint($record);
            });
    }
}
