<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('device.{deviceId}', function (User $user, string $deviceId) {
    return $user->devices()->where('device_id', $deviceId)->exists()
        || $user->hasRole('admin');
});

Broadcast::channel('device.{deviceId}', function (Device $device, string $deviceId) {
    return $device->device_id === $deviceId;
});

Broadcast::channel('admin.transactions', function (User $user) {
    return $user->hasRole('admin');
});
