<?php

namespace App\Services;

use App\Events\TransactionCompleted;
use App\Models\Device;
use App\Models\RfidCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function processTap(Transaction $transaction, string $uid): array
    {
        if (! $transaction->isPending()) {
            return [
                'success' => false,
                'message' => 'Transaksi tidak dalam status pending',
                'code' => 'INVALID_STATUS',
            ];
        }

        if ($transaction->isExpired()) {
            $transaction->markCancelled();

            return [
                'success' => false,
                'message' => 'Transaksi kadaluarsa',
                'code' => 'EXPIRED',
            ];
        }

        $card = RfidCard::where('uid', $uid)->first();

        if (! $card) {
            return [
                'success' => false,
                'message' => 'Kartu tidak terdaftar',
                'code' => 'CARD_NOT_FOUND',
            ];
        }

        if (! $card->is_active) {
            return [
                'success' => false,
                'message' => 'Kartu tidak aktif',
                'code' => 'CARD_INACTIVE',
            ];
        }

        if (! $card->hasSufficientBalance($transaction->total)) {
            return [
                'success' => false,
                'message' => 'Saldo tidak mencukupi',
                'code' => 'INSUFFICIENT_BALANCE',
                'balance' => $card->balance,
                'required' => $transaction->total,
            ];
        }

        return DB::transaction(function () use ($transaction, $card) {
            $balanceBefore = $card->balance;
            $card->deductBalance($transaction->total);
            $balanceAfter = $card->fresh()->balance;

            $transaction->update([
                'rfid_card_id' => $card->id,
                'status' => 'success',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            broadcast(new TransactionCompleted($transaction))->toOthers();

            return [
                'success' => true,
                'message' => 'Transaksi berhasil',
                'transaction' => [
                    'id' => $transaction->id,
                    'total' => $transaction->total,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'card' => [
                        'uid' => $card->uid,
                        'user_name' => $card->user_name,
                    ],
                ],
            ];
        });
    }

    public function createPendingTransaction(
        Device $device,
        array $items
    ): Transaction {
        $total = collect($items)->sum(fn ($i) => $i['subtotal']);

        return Transaction::create([
            'device_id' => $device->id,
            'total' => $total,
            'status' => 'pending',
            'items' => $items,
            'balance_before' => 0,
            'balance_after' => 0,
            'expires_at' => now()->addSeconds(30),
        ]);
    }
}
