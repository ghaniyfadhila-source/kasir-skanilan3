<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
 * Every endpoint below is rate limited and authenticated. The device routes
 * only accept a console bearer token; the transaction routes accept that token
 * or a logged in kasir session, because the POS polls the status from the
 * browser.
 */
Route::prefix('device')->group(function () {
    Route::post('register', [DeviceController::class, 'register'])->middleware('throttle:api-device');

    Route::middleware(['auth.device', 'throttle:api-device'])->group(function () {
        Route::get('{device:device_id}/pending-transaction', [DeviceController::class, 'pendingTransaction']);
        Route::post('{device:device_id}/tap', [DeviceController::class, 'tap']);
        Route::post('{device:device_id}/heartbeat', [DeviceController::class, 'heartbeat']);
    });
});

Route::prefix('transactions')
    ->middleware(['auth.api', 'throttle:api-transaction'])
    ->group(function () {
        Route::post('/', [TransactionController::class, 'create']);
        Route::post('{transaction}/cancel', [TransactionController::class, 'cancel']);
        Route::get('{transaction}/status', [TransactionController::class, 'status']);
    });
