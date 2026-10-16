<?php

namespace App\Http\Controllers\Api;

use App\Events\TransactionCancelled;
use App\Events\TransactionCreated;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function create(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,device_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $device = Device::where('device_id', $request->device_id)->firstOrFail();

        $this->ensureCallerIsConsole($request, $device);

        $items = [];
        $total = 0;

        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $subtotal = $product->price * $item['qty'];
            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'qty' => $item['qty'],
                'price' => $product->price,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        $transaction = Transaction::create([
            'device_id' => $device->id,
            'rfid_card_id' => null,
            'total' => $total,
            'status' => 'pending',
            'items' => $items,
            'balance_before' => 0,
            'balance_after' => 0,
            'expires_at' => now()->addSeconds(30),
        ]);

        broadcast(new TransactionCreated($transaction))->toOthers();

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'total' => $transaction->total,
                'items' => $transaction->items,
                'expires_at' => $transaction->expires_at->toISOString(),
            ],
        ]);
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        if (! $transaction->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak bisa dibatalkan',
            ], 400);
        }

        $transaction->markCancelled();

        broadcast(new TransactionCancelled($transaction))->toOthers();

        return response()->json(['success' => true]);
    }

    public function status(Request $request, Transaction $transaction)
    {
        $this->ensureTransactionBelongsToCaller($request, $transaction);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'total' => $transaction->total,
                'items' => $transaction->items,
            ],
        ]);
    }

    /**
     * Answer 404 instead of 403 for someone else's transaction: a caller must
     * not be able to probe which transaction ids exist.
     */
    private function ensureTransactionBelongsToCaller(Request $request, Transaction $transaction): void
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return;
        }

        $device = $request->input('authenticated_device');

        if (! $device || $device->id !== $transaction->device_id) {
            abort(404);
        }
    }

    /**
     * Creating a pending transaction may only be done for the console the
     * caller is: a token or session for console A cannot open sales on B.
     */
    private function ensureCallerIsConsole(Request $request, Device $device): void
    {
        $caller = $request->input('authenticated_device');

        if (! $caller || $caller->id !== $device->id) {
            abort(403, 'Token tidak sesuai device');
        }
    }
}
