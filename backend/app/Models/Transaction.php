<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'rfid_card_id',
        'total',
        'status',
        'failure_reason',
        'items',
        'balance_before',
        'balance_after',
        'expires_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'items' => 'array',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'expires_at' => 'datetime',
        'status' => 'string',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function rfidCard()
    {
        return $this->belongsTo(RfidCard::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->isPending() && $this->expires_at && $this->expires_at->isPast();
    }

    public function markSuccess(): void
    {
        $this->update(['status' => 'success']);
    }

    public function markFailed(string $reason = ''): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason !== '' ? $reason : null,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
