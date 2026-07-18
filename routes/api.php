<?php

use App\Http\Controllers\VehicleController;

Route::prefix('vehicles')->controller(VehicleController::class)->group(function () {
    Route::post('/ask', 'search')->name('search');
});
