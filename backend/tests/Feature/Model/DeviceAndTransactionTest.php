<?php

use App\Models\Device;
use App\Models\RfidCard;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();
    $this->device = $this->kasir->device;
    $this->card = RfidCard::firstOrFail();
});

it('casts the device metadata and last seen timestamp', function () {
    $device = Device::create([
        'device_id' => 'ESP8266_META',
        'name' => 'Console Metadata',
        'status' => 'active',
        'api_token' => 'dev_meta',
        'metadata' => ['firmware' => '2.0.0', 'signal' => -61],
        'last_seen_at' => '2026-01-01 10:00:00',
    ]);

    expect($device->metadata)->toEqual(['firmware' => '2.0.0', 'signal' => -61])
        ->and($device->last_seen_at->format('Y-m-d H:i'))->toEqual('2026-01-01 10:00');
});

it('generates a prefixed api token', function () {
    $token = $this->device->generateApiToken();

    expect($token)->toStartWith('dev_')
        ->and(strlen($token))->toEqual(68)
        ->and($token)->toMatch('/^dev_[0-9a-f]{64}$/');
});

it('treats a console as online only within 30 seconds', function () {
    $this->device->update(['last_seen_at' => now()]);
    expect($this->device->fresh()->isOnline())->toBeTrue();

    $this->device->update(['last_seen_at' => now()->subSeconds(45)]);
    expect($this->device->fresh()->isOnline())->toBeFalse();

    $this->device->update(['last_seen_at' => null]);
    expect($this->device->fresh()->isOnline())->toBeFalse();
});

it('links a device to its kasir and its transactions', function () {
    $transaction = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'balance_before' => 0,
        'balance_after' => 0,
    ]);

    expect($this->device->kasir->id)->toEqual($this->kasir->id)
        ->and($this->device->transactions()->count())->toEqual(1)
        ->and($this->device->transactions()->first()->id)->toEqual($transaction->id);
});

it('moves a transaction through its status helpers', function () {
    $transaction = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->addSeconds(30),
    ]);

    expect($transaction->isPending())->toBeTrue()
        ->and($transaction->isExpired())->toBeFalse()
        ->and($transaction->rfid_card_id)->toBeNull();

    $transaction->markSuccess();
    expect($transaction->fresh()->status)->toEqual('success')
        ->and($transaction->fresh()->isPending())->toBeFalse();

    $failed = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->addSeconds(30),
    ]);
    $failed->markFailed();
    expect($failed->fresh()->status)->toEqual('failed');

    $cancelled = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->addSeconds(30),
    ]);
    $cancelled->markCancelled();
    expect($cancelled->fresh()->status)->toEqual('cancelled');
});

it('knows when a pending transaction ran out of time', function () {
    $expired = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->subSecond(),
    ]);

    expect($expired->isExpired())->toBeTrue();

    $done = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->subMinute(),
    ]);

    // A finished transaction is never "expired", it is simply no longer pending.
    expect($done->isExpired())->toBeFalse();
});

it('links a transaction to the card that paid for it', function () {
    $transaction = Transaction::create([
        'device_id' => $this->device->id,
        'rfid_card_id' => $this->card->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
    ]);

    expect($transaction->rfidCard->uid)->toEqual($this->card->uid)
        ->and((float) $transaction->balance_after)->toEqual(35000.0)
        ->and($transaction->items)->toBeArray();
});
