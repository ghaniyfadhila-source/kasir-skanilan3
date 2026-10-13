<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->hasRole('admin');
    }
}