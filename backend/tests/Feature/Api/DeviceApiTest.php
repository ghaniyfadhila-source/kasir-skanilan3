<?php

use App\Models\Device;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->device = Device::where('device_id', 'ESP8266_DEMO_001')->first();
});

it('allows device to get pending transaction', function () {
    $transaction = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Test', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'expires_at' => now()->addSeconds(30),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->getJson("/api/device/{$this->device->device_id}/pending-transaction");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'has_pending' => true,
            'transaction' => [
                'id' => $transaction->id,
                'total' => 15000,
            ],
        ]);
});

it('returns has_pending false when no pending transaction', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->getJson("/api/device/{$this->device->device_id}/pending-transaction");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'has_pending' => false,
        ]);
});

it('rejects access to other device pending transaction', function () {
    $otherDevice = Device::create([
        'device_id' => 'OTHER_DEVICE_002',
        'name' => 'Other Device',
        'api_token' => 'dev_other_token',
        'status' => 'active',
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->getJson("/api/device/{$otherDevice->device_id}/pending-transaction");

    $response->assertStatus(403);
});

it('allows device to send heartbeat', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$this->device->device_id}/heartbeat", [
        'rssi' => -50,
        'free_heap' => 30000,
        'uptime' => 3600,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->device->refresh();
    $this->assertNotNull($this->device->last_seen_at);
    $this->assertEquals($this->device->metadata['rssi'], -50);
});

it('rejects heartbeat from other device token', function () {
    $otherDevice = Device::create([
        'device_id' => 'OTHER_DEVICE_002',
        'name' => 'Other Device',
        'api_token' => 'dev_other_token',
        'status' => 'active',
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->device->api_token,
    ])->postJson("/api/device/{$otherDevice->device_id}/heartbeat", [
        'rssi' => -50,
    ]);

    $response->assertStatus(403);
});
