<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.'.$this->transaction->device->device_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->transaction->id,
            'message' => 'Transaksi dibatalkan',
        ];
    }
}
