<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('device.{deviceId}', function ($user, string $deviceId) {
    if ($user instanceof User) {
        return $user->devices()->where('device_id', $deviceId)->exists()
            || $user->hasRole('admin');
    }

    if ($user instanceof Device) {
        return $user->device_id === $deviceId;
    }

    return false;
});

Broadcast::channel('admin.transactions', function (User $user) {
    return $user->hasRole('admin');
});
