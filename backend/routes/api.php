<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('device')->group(function () {
    Route::post('register', [DeviceController::class, 'register']);
    Route::get('{device:device_id}/pending-transaction', [DeviceController::class, 'pendingTransaction']);
    Route::post('{device:device_id}/tap', [DeviceController::class, 'tap']);
    Route::post('{device:device_id}/heartbeat', [DeviceController::class, 'heartbeat']);
});

Route::prefix('transactions')->group(function () {
    Route::post('/', [TransactionController::class, 'create']);
    Route::post('{transaction}/cancel', [TransactionController::class, 'cancel']);
    Route::get('{transaction}/status', [TransactionController::class, 'status']);
});