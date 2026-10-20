<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Device extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'device_id',
        'name',
        'status',
        'api_token',
        'firmware_version',
        'ip_address',
        'metadata',
        'last_seen_at',
        'kasir_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function generateApiToken(): string
    {
        return 'dev_'.bin2hex(random_bytes(32));
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->diffInSeconds(now()) < 30;
    }
}
