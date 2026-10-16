<?php

use App\Filament\Resources\DeviceResource\Pages\ListDevices;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\RfidCardResource\Pages\ListRfidCards;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Device;
use App\Models\Product;
use App\Models\RfidCard;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin = User::where('email', 'admin@skanilan.local')->firstOrFail();
    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();
    $this->actingAs($this->admin);
});

it('shows the device list with its create action', function () {
    Livewire::test(ListDevices::class)
        ->assertCanSeeTableRecords(Device::all())
        ->assertOk();
});

it('shows the product list and can search it', function () {
    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords(Product::all())
        ->searchTable('Nasi Goreng')
        ->assertCanSeeTableRecords(Product::where('name', 'Nasi Goreng')->get())
        ->assertCanNotSeeTableRecords(Product::where('name', 'Es Jeruk')->get());
});

it('shows every transaction for an admin', function () {
    $transaction = Transaction::create([
        'device_id' => $this->kasir->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
    ]);

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords([$transaction])
        ->assertOk();
});

it('shows the card list', function () {
    Livewire::test(ListRfidCards::class)
        ->assertCanSeeTableRecords(RfidCard::all())
        ->assertOk();
});

it('scopes the transaction list to the console of a kasir', function () {
    $this->actingAs($this->kasir);

    $own = Transaction::create([
        'device_id' => $this->kasir->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
    ]);

    $foreign = Transaction::create([
        'device_id' => Device::create([
            'device_id' => 'ESP8266_LAIN',
            'name' => 'Console Orang Lain',
            'status' => 'active',
            'api_token' => 'dev_lain',
        ])->id,
        'total' => 25000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 40000,
        'balance_after' => 15000,
    ]);

    Livewire::test(ListTransactions::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});
