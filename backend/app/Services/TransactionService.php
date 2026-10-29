<?php

namespace App\Services;

use App\Events\TransactionCompleted;
use App\Models\Device;
use App\Models\Product;
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
            $transaction->markFailed('Kartu tidak terdaftar');

            return [
                'success' => false,
                'message' => 'Kartu tidak terdaftar',
                'code' => 'CARD_NOT_FOUND',
            ];
        }

        if (! $card->is_active) {
            $transaction->markFailed('Kartu tidak aktif');

            return [
                'success' => false,
                'message' => 'Kartu tidak aktif',
                'code' => 'CARD_INACTIVE',
            ];
        }

        if (! $card->hasSufficientBalance($transaction->total)) {
            $transaction->markFailed('Saldo tidak mencukupi');

            return [
                'success' => false,
                'message' => 'Saldo tidak mencukupi',
                'code' => 'INSUFFICIENT_BALANCE',
                'balance' => (float) $card->balance,
                'required' => (float) $transaction->total,
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

            foreach ($transaction->items as $item) {
                Product::where('id', $item['product_id'])->decrement('stock', $item['qty']);
            }

            broadcast(new TransactionCompleted($transaction))->toOthers();

            return [
                'success' => true,
                'message' => 'Transaksi berhasil',
                'transaction' => [
                    'id' => $transaction->id,
                    'total' => (float) $transaction->total,
                    'balance_before' => (float) $balanceBefore,
                    'balance_after' => (float) $balanceAfter,
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
            // The 30 second tap window starts when the console actually
            // collects the bill (DeviceController::pendingTransaction), so a
            // console that is offline does not burn it before showing anything.
            'expires_at' => null,
        ]);
    }
}
