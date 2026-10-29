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

    $this->otherKasir = User::create([
        'name' => 'Kasir Kedua',
        'email' => 'kasir.kedua@skanilan.local',
        'password' => 'password123',
    ]);
    $this->otherKasir->assignRole('kasir');

    $this->otherDevice = Device::create([
        'device_id' => 'ESP8266_KASIR_2',
        'name' => 'Console Kasir 2',
        'status' => 'active',
        'api_token' => 'dev_second_console_token',
        'kasir_id' => $this->otherKasir->id,
    ]);
});

it('lets only an admin manage devices', function () {
    expect($this->admin->can('viewAny', Device::class))->toBeTrue()
        ->and($this->admin->can('create', Device::class))->toBeTrue()
        ->and($this->admin->can('view', $this->device))->toBeTrue()
        ->and($this->admin->can('update', $this->device))->toBeTrue()
        ->and($this->admin->can('delete', $this->device))->toBeTrue();

    expect($this->kasir->can('viewAny', Device::class))->toBeFalse()
        ->and($this->kasir->can('create', Device::class))->toBeFalse()
        ->and($this->kasir->can('view', $this->device))->toBeFalse()
        ->and($this->kasir->can('update', $this->device))->toBeFalse()
        ->and($this->kasir->can('delete', $this->device))->toBeFalse();
});

it('lets a kasir manage the products they own', function () {
    expect($this->kasir->can('viewAny', Product::class))->toBeTrue()
        ->and($this->kasir->can('create', Product::class))->toBeTrue()
        ->and($this->kasir->can('view', $this->kasir->products()->first()))->toBeTrue()
        ->and($this->kasir->can('update', $this->kasir->products()->first()))->toBeTrue()
        ->and($this->kasir->can('delete', $this->kasir->products()->first()))->toBeTrue();
});

it('stops a kasir from touching another kasir products', function () {
    $foreign = Product::create([
        'kasir_id' => $this->otherKasir->id,
        'name' => 'Milik Kasir Kedua',
        'price' => 1000,
        'stock' => 5,
        'is_active' => true,
    ]);

    expect($this->kasir->can('view', $foreign))->toBeFalse()
        ->and($this->kasir->can('update', $foreign))->toBeFalse()
        ->and($this->kasir->can('delete', $foreign))->toBeFalse();
});

it('lets an admin manage every product', function () {
    $foreign = Product::create([
        'kasir_id' => $this->otherKasir->id,
        'name' => 'Milik Kasir Kedua',
        'price' => 1000,
        'stock' => 5,
        'is_active' => true,
    ]);

    expect($this->admin->can('view', $foreign))->toBeTrue()
        ->and($this->admin->can('update', $foreign))->toBeTrue()
        ->and($this->admin->can('delete', $foreign))->toBeTrue();
});

it('scopes transaction access to the console of the kasir', function () {
    $own = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
    ]);

    $foreign = Transaction::create([
        'device_id' => $this->otherDevice->id,
        'total' => 25000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 40000,
        'balance_after' => 15000,
    ]);

    expect($this->kasir->can('viewAny', Transaction::class))->toBeTrue()
        ->and($this->kasir->can('view', $own))->toBeTrue()
        ->and($this->kasir->can('update', $own))->toBeFalse()
        ->and($this->kasir->can('view', $foreign))->toBeFalse()
        ->and($this->kasir->can('update', $foreign))->toBeFalse()
        ->and($this->kasir->can('cancel', $own))->toBeTrue()
        ->and($this->kasir->can('cancel', $foreign))->toBeFalse()
        ->and($this->admin->can('update', $own))->toBeTrue();
});

it('reserves deleting transactions for an admin', function () {
    $transaction = Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
    ]);

    expect($this->kasir->can('delete', $transaction))->toBeFalse()
        ->and($this->admin->can('delete', $transaction))->toBeTrue()
        ->and($this->admin->can('view', $transaction))->toBeTrue();
});
