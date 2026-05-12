<?php

use Illuminate\Support\Facades\Route;
use Modules\Diagnostics\Http\Controllers\DiagnosticsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('diagnostics', DiagnosticsController::class)->names('diagnostics');
});
