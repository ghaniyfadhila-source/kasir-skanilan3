<?php

use App\Models\Device;
use App\Models\RfidCard;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->device = Device::where('device_id', 'ESP8266_DEMO_001')->first();
    $this->card = RfidCard::where('uid', 'A1B2C3D4')->first();
});

it('processes tap successfully with valid card', function () {
    $transaction = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Test', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'expires_at' => now()->addSeconds(30),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/tap", [
        'uid' => 'a1b2c3d4', // lowercase to test case-insensitive
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Transaksi berhasil',
            'transaction' => [
                'id' => $transaction->id,
                'total' => 15000,
                'balance_before' => 50000,
                'balance_after' => 35000,
                'card' => [
                    'uid' => 'A1B2C3D4',
                    'user_name' => 'Budi Santoso',
                ],
            ],
        ]);

    $this->card->refresh();
    $this->assertEquals(35000, $this->card->balance);

    $transaction->refresh();
    $this->assertEquals('success', $transaction->status);
    $this->assertEquals($this->card->id, $transaction->rfid_card_id);
});

it('rejects tap with no pending transaction', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/tap", [
        'uid' => 'A1B2C3D4',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'code' => 'NO_PENDING_TRANSACTION',
        ]);
});

it('rejects tap with unknown card', function () {
    Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Test', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'expires_at' => now()->addSeconds(30),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/tap", [
        'uid' => 'UNKNOWN_CARD',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'code' => 'CARD_NOT_FOUND',
        ]);
});

it('rejects tap with inactive card', function () {
    $this->card->update(['is_active' => false]);

    Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Test', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'expires_at' => now()->addSeconds(30),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/tap", [
        'uid' => 'A1B2C3D4',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'code' => 'CARD_INACTIVE',
        ]);
});

it('rejects tap with insufficient balance', function () {
    Transaction::create([
        'device_id' => $this->device->id,
        'total' => 100000, // more than card balance (50000)
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Test', 'qty' => 1, 'price' => 100000, 'subtotal' => 100000]],
        'expires_at' => now()->addSeconds(30),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/tap", [
        'uid' => 'A1B2C3D4',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'code' => 'INSUFFICIENT_BALANCE',
            'balance' => 50000,
            'required' => 100000,
        ]);
});

it('rejects tap with expired transaction', function () {
    Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Test', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'expires_at' => now()->subSeconds(10),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/tap", [
        'uid' => 'A1B2C3D4',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'code' => 'EXPIRED',
        ]);
});
