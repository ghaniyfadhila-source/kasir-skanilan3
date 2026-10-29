<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Filament only allows unrestricted panel access in the local environment
     * when the model does not implement FilamentUser. Both seeded roles are
     * valid panel users; individual resources and pages handle the rest of the
     * authorization.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['admin', 'kasir']);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function device()
    {
        // A kasir owns the device; devices.kasir_id is the foreign key.
        return $this->hasOne(Device::class, 'kasir_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'kasir_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isKasir(): bool
    {
        return $this->hasRole('kasir');
    }
}
