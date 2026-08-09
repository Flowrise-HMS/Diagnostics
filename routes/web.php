<?php

use Illuminate\Support\Facades\Route;
use Modules\Diagnostics\Http\Controllers\DiagnosticLabResultPrintController;
use Modules\Diagnostics\Http\Controllers\DiagnosticResultFileController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('diagnostics/fulfillments/{fulfillment}/lab-result/print', [DiagnosticLabResultPrintController::class, 'show'])
        ->name('diagnostics.fulfillments.lab-result.print');

    Route::get('diagnostics/result-files/{resultFile}/download', [DiagnosticResultFileController::class, 'download'])
        ->middleware('signed')
        ->name('diagnostics.result-files.download');
});
