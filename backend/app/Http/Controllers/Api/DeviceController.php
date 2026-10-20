<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function register(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:100',
            'firmware_version' => 'nullable|string|max:20',
        ]);

        $device = Device::firstOrCreate(
            ['device_id' => $request->device_id],
            [
                // A label is required: Filament's Select crashes on a null
                // option label, so an API-registered console must never leave
                // name empty.
                'name' => $request->device_id,
                'firmware_version' => $request->firmware_version,
                'api_token' => Device::generateApiToken(),
                'status' => 'active',
            ]
        );

        if ($request->firmware_version) {
            $device->update(['firmware_version' => $request->firmware_version]);
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'name' => $device->name,
                'status' => $device->status,
                'api_token' => $device->api_token,
            ],
        ]);
    }

    public function pendingTransaction(Request $request, Device $device)
    {
        $authenticatedDevice = $request->get('authenticated_device');
        if (! $authenticatedDevice || $authenticatedDevice->device_id !== $device->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $transaction = $device->transactions()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => true,
                'has_pending' => false,
            ]);
        }

        // The console has no clock (no NTP), so it cannot turn the absolute
        // expiry into a millis() deadline. remaining_seconds is the only field
        // the firmware needs to time a transaction out.
        $remainingSeconds = $transaction->expires_at
            ? max(0, (int) now()->diffInSeconds($transaction->expires_at, false))
            : null;

        return response()->json([
            'success' => true,
            'has_pending' => true,
            'transaction' => [
                'id' => $transaction->id,
                'total' => (float) $transaction->total,
                'items' => $transaction->items,
                'expires_at' => $transaction->expires_at?->toISOString(),
                'remaining_seconds' => $remainingSeconds,
            ],
        ]);
    }

    public function tap(Request $request, Device $device)
    {
        $authenticatedDevice = $request->get('authenticated_device');
        if (! $authenticatedDevice || $authenticatedDevice->device_id !== $device->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'code' => 'UNAUTHORIZED',
            ], 403);
        }

        $request->validate([
            'uid' => 'required|string|max:50',
        ]);

        $uid = strtoupper($request->uid);

        $pendingTransaction = $device->transactions()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $pendingTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada transaksi menunggu',
                'code' => 'NO_PENDING_TRANSACTION',
            ], 400);
        }

        $result = $this->transactionService->processTap($pendingTransaction, $uid);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function heartbeat(Request $request, Device $device)
    {
        $authenticatedDevice = $request->get('authenticated_device');
        if (! $authenticatedDevice || $authenticatedDevice->device_id !== $device->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'rssi' => 'nullable|integer',
            'free_heap' => 'nullable|integer',
            'uptime' => 'nullable|integer',
        ]);

        $device->update([
            'last_seen_at' => now(),
            'ip_address' => $request->ip(),
            'metadata' => array_merge($device->metadata ?? [], $request->only(['rssi', 'free_heap', 'uptime'])),
        ]);

        return response()->json(['success' => true]);
    }
}
