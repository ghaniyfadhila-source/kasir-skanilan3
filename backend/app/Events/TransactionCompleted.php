<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.' . $this->transaction->device_id),
            new PrivateChannel('admin.transactions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->transaction->id,
            'status' => $this->transaction->status,
            'total' => $this->transaction->total,
            'balance_before' => $this->transaction->balance_before,
            'balance_after' => $this->transaction->balance_after,
            'card' => $this->transaction->rfidCard ? [
                'uid' => $this->transaction->rfidCard->uid,
                'user_name' => $this->transaction->rfidCard->user_name,
            ] : null,
        ];
    }
}