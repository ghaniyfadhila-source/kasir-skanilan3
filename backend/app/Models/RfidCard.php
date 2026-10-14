<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'user_name',
        'balance',
        'is_active',
        'registered_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function setUidAttribute(string $value): void
    {
        $this->attributes['uid'] = strtoupper($value);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->is_active && $this->balance >= $amount;
    }

    public function deductBalance(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }
}
