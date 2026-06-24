<?php

use Illuminate\Support\Facades\Route;
use Modules\Diagnostics\Http\Controllers\DiagnosticLabResultPrintController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('diagnostics/fulfillments/{fulfillment}/lab-result/print', [DiagnosticLabResultPrintController::class, 'show'])
        ->name('diagnostics.fulfillments.lab-result.print');
});
