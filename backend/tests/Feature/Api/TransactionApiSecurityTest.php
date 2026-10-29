<?php

use App\Models\Device;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin = User::where('email', 'admin@skanilan.local')->firstOrFail();
    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();
    $this->device = $this->kasir->device;
    $this->product = Product::where('name', 'Nasi Goreng')->firstOrFail();

    // A second console, so "someone else's transaction" can be tested.
    $this->otherDevice = Device::create([
        'device_id' => 'ESP8266_KASIR_2',
        'name' => 'Console Kasir 2',
        'status' => 'active',
        'api_token' => 'dev_token_kasir_2',
        'kasir_id' => User::create([
            'name' => 'Kasir Kedua',
            'email' => 'kasir.kedua@skanilan.local',
            'password' => 'password123',
        ])->id,
    ]);

    $this->ownTransaction = pendingTransaction($this->device);
    $this->foreignTransaction = pendingTransaction($this->otherDevice);
});

it('rejects an anonymous caller on every transaction endpoint', function () {
    $this->getJson("/api/transactions/{$this->ownTransaction->id}/status")->assertUnauthorized();
    $this->postJson("/api/transactions/{$this->ownTransaction->id}/cancel")->assertUnauthorized();

    $this->postJson('/api/transactions', [
        'device_id' => $this->device->device_id,
        'items' => [['product_id' => $this->product->id, 'qty' => 1]],
    ])->assertUnauthorized();
});

it('rejects a bogus bearer token', function () {
    $this->withHeaders(['Authorization' => 'Bearer token-palsu'])
        ->getJson("/api/transactions/{$this->ownTransaction->id}/status")
        ->assertUnauthorized();
});

it('lets a kasir read the status of their own console transaction', function () {
    $this->actingAs($this->kasir)
        ->getJson("/api/transactions/{$this->ownTransaction->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'transaction' => [
                'id' => $this->ownTransaction->id,
                'status' => 'pending',
            ],
        ]);
});

it('hides transactions of another console from a kasir', function () {
    // 404 rather than 403, so ids cannot be probed one by one.
    $this->actingAs($this->kasir)
        ->getJson("/api/transactions/{$this->foreignTransaction->id}/status")
        ->assertNotFound();
});

it('lets an admin read any transaction', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/transactions/{$this->foreignTransaction->id}/status")
        ->assertOk()
        ->assertJson(['transaction' => ['id' => $this->foreignTransaction->id]]);
});

it('lets a console read its own transaction with its token', function () {
    $this->withHeaders(['Authorization' => 'Bearer '.$this->device->api_token])
        ->getJson("/api/transactions/{$this->ownTransaction->id}/status")
        ->assertOk()
        ->assertJson(['transaction' => ['id' => $this->ownTransaction->id]]);
});

it('hides other transactions from a console token', function () {
    $this->withHeaders(['Authorization' => 'Bearer '.$this->device->api_token])
        ->getJson("/api/transactions/{$this->foreignTransaction->id}/status")
        ->assertNotFound();
});

it('lets a kasir cancel their own pending transaction', function () {
    $this->actingAs($this->kasir)
        ->postJson("/api/transactions/{$this->ownTransaction->id}/cancel")
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($this->ownTransaction->fresh()->status)->toEqual('cancelled');
});

it('stops a kasir from cancelling another console transaction', function () {
    $this->actingAs($this->kasir)
        ->postJson("/api/transactions/{$this->foreignTransaction->id}/cancel")
        ->assertForbidden();

    expect($this->foreignTransaction->fresh()->status)->toEqual('pending');
});

it('creates a pending transaction for the caller own console', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->device->api_token])
        ->postJson('/api/transactions', [
            'device_id' => $this->device->device_id,
            'items' => [['product_id' => $this->product->id, 'qty' => 2]],
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $transaction = Transaction::where('status', 'pending')->latest('id')->first();

    expect($transaction->device_id)->toEqual($this->device->id)
        ->and((float) $transaction->total)->toEqual(30000.0)
        ->and($transaction->items[0]['qty'])->toEqual(2)
        ->and($transaction->expires_at)->toBeNull();
});

it('refuses to open a transaction for another console', function () {
    $this->withHeaders(['Authorization' => 'Bearer '.$this->device->api_token])
        ->postJson('/api/transactions', [
            'device_id' => $this->otherDevice->device_id,
            'items' => [['product_id' => $this->product->id, 'qty' => 1]],
        ])
        ->assertForbidden();

    expect(Transaction::where('status', 'pending')->count())->toEqual(2); // only the two fixtures
});

function pendingTransaction(Device $device): Transaction
{
    return Transaction::create([
        'device_id' => $device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Nasi Goreng', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->addSeconds(30),
    ]);
}
