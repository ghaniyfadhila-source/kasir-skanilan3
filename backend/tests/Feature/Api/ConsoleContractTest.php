<?php

use App\Models\Device;
use App\Models\Product;
use App\Models\RfidCard;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The ESP8266 firmware parses these responses field by field (see
 * firmware/src/network.cpp). These tests replay the exact payloads the console
 * sends and lock the names and types the firmware reads, so an API change
 * cannot silently brick the hardware.
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->device = Device::where('device_id', 'ESP8266_DEMO_001')->first();
    $this->headers = ['Authorization' => 'Bearer '.$this->device->api_token];
    $this->card = RfidCard::where('uid', 'E5F6G7H8')->first(); // Siti, Rp 75.000
});

function consolePendingTransaction(Device $device, array $items, int $total, int $seconds = 30): Transaction
{
    return Transaction::create([
        'device_id' => $device->id,
        'total' => $total,
        'status' => 'pending',
        'items' => $items,
        'expires_at' => now()->addSeconds($seconds),
    ]);
}

it('returns the same token when the console re-registers after a reboot', function () {
    $payload = [
        'device_id' => 'ESP8266_DEMO_001',
        'firmware_version' => '1.0.0',
    ];

    $first = $this->postJson('/api/device/register', $payload);
    $second = $this->postJson('/api/device/register', $payload);

    $first->assertOk()->assertJsonPath('success', true);
    $second->assertOk()->assertJsonPath('success', true);

    expect($first->json('device.api_token'))->toBe($this->device->api_token)
        ->and($second->json('device.api_token'))->toBe($this->device->api_token);
});

it('registers an unknown console as active so the firmware can poll', function () {
    $response = $this->postJson('/api/device/register', [
        'device_id' => 'ESP8266_AABBCCDDEEFF',
        'firmware_version' => '1.0.0',
    ]);

    $response->assertOk()
        ->assertJsonPath('device.device_id', 'ESP8266_AABBCCDDEEFF')
        ->assertJsonPath('device.name', 'ESP8266_AABBCCDDEEFF')
        ->assertJsonPath('device.status', 'active');

    expect($response->json('device.api_token'))->not->toBeNull();
});

it('exposes the pending transaction fields the firmware parses', function () {
    $product = Product::where('name', 'Tahu Goreng')->first();
    $transaction = consolePendingTransaction($this->device, [[
        'product_id' => $product->id,
        'name' => $product->name,
        'qty' => 2,
        'price' => $product->price,
        'subtotal' => $product->price * 2,
    ]], $product->price * 2);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/device/{$this->device->device_id}/pending-transaction");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('has_pending', true)
        ->assertJsonPath('transaction.id', $transaction->id)
        ->assertJsonPath('transaction.items.0.qty', 2)
        ->assertJsonStructure([
            'transaction' => [
                'id',
                'total',
                'items' => [['product_id', 'name', 'qty', 'price', 'subtotal']],
                'expires_at',
                'remaining_seconds',
            ],
        ]);

    $remaining = $response->json('transaction.remaining_seconds');

    expect((float) $response->json('transaction.total'))->toBe((float) ($product->price * 2))
        ->and($remaining)->toBeInt()
        ->and($remaining)->toBeGreaterThan(20)
        ->and($remaining)->toBeLessThanOrEqual(30);
    expect($response->json('transaction.expires_at'))->toBeString();
});

it('reports an expired pending transaction as no longer pending', function () {
    $product = Product::where('name', 'Tahu Goreng')->first();
    consolePendingTransaction($this->device, [[
        'product_id' => $product->id,
        'name' => $product->name,
        'qty' => 1,
        'price' => $product->price,
        'subtotal' => $product->price,
    ]], $product->price, -1);

    $this->withHeaders($this->headers)
        ->getJson("/api/device/{$this->device->device_id}/pending-transaction")
        ->assertOk()
        ->assertJsonPath('has_pending', false);
});

it('completes a card tap and returns the balances shown on the LCD', function () {
    $product = Product::where('name', 'Tahu Goreng')->first();
    $stockBefore = $product->stock;
    $balanceBefore = $this->card->balance;
    $total = $product->price * 2;

    $transaction = consolePendingTransaction($this->device, [[
        'product_id' => $product->id,
        'name' => $product->name,
        'qty' => 2,
        'price' => $product->price,
        'subtotal' => $total,
    ]], $total);

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/device/{$this->device->device_id}/tap", ['uid' => $this->card->uid]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('transaction.card.uid', $this->card->uid)
        ->assertJsonPath('transaction.card.user_name', $this->card->user_name);

    expect((float) $response->json('transaction.balance_before'))->toBe((float) $balanceBefore)
        ->and((float) $response->json('transaction.balance_after'))->toBe((float) $balanceBefore - $total)
        ->and((float) $this->card->fresh()->balance)->toBe((float) $balanceBefore - $total)
        ->and($product->fresh()->stock)->toBe($stockBefore - 2)
        ->and($transaction->fresh()->status)->toBe('success');
});

it('answers a tap without a pending transaction with the code the LCD maps', function () {
    $this->withHeaders($this->headers)
        ->postJson("/api/device/{$this->device->device_id}/tap", ['uid' => $this->card->uid])
        ->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'NO_PENDING_TRANSACTION')
        ->assertJsonPath('message', 'Tidak ada transaksi menunggu');
});

it('answers an underfunded tap with a message instead of a bare HTTP error', function () {
    $product = Product::where('name', 'Ayam Goreng')->first();
    $transaction = consolePendingTransaction($this->device, [[
        'product_id' => $product->id,
        'name' => $product->name,
        'qty' => 999,
        'price' => $product->price,
        'subtotal' => $product->price * 999,
    ]], $product->price * 999);

    $this->withHeaders($this->headers)
        ->postJson("/api/device/{$this->device->device_id}/tap", ['uid' => $this->card->uid])
        ->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'INSUFFICIENT_BALANCE')
        ->assertJsonPath('message', 'Saldo tidak mencukupi');

    // The console abandons the attempt, so the POS must stop polling and show
    // the same reason the LCD shows.
    $this->withHeaders($this->headers)
        ->getJson("/api/transactions/{$transaction->id}/status")
        ->assertOk()
        ->assertJsonPath('transaction.status', 'failed')
        ->assertJsonPath('transaction.message', 'Saldo tidak mencukupi');
});

it('accepts the console heartbeat payload and marks the device online', function () {
    $this->withHeaders($this->headers)
        ->postJson("/api/device/{$this->device->device_id}/heartbeat", [
            'rssi' => -57,
            'free_heap' => 28432,
            'uptime' => 120,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->device->refresh();

    expect($this->device->last_seen_at)->not->toBeNull()
        ->and($this->device->metadata['rssi'])->toBe(-57)
        ->and($this->device->metadata['free_heap'])->toBe(28432);
});
