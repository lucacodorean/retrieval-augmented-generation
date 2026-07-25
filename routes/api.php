<?php

use App\Http\Controllers\EmailSendController;
use App\Http\Controllers\VehicleController;

Route::prefix('vehicles')->controller(VehicleController::class)->group(function () {
    Route::post('/ask', 'search')->name('search');
});

Route::prefix('vehicles')->controller(EmailSendController::class)->group(function () {
   Route::post('/send', 'startWorkflow')->name('send');
   Route::post('/interrupt-check', 'checkWorkflowInterrupts')->name('check');
});
