<?php

use App\Filament\Resources\DeviceResource\Pages\CreateDevice;
use App\Filament\Resources\DeviceResource\Pages\ListDevices;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\RfidCardResource\Pages\ListRfidCards;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Device;
use App\Models\Product;
use App\Models\RfidCard;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

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

it('rejects a second device for a kasir who already has one', function () {
    Livewire::test(CreateDevice::class)
        ->fillForm([
            'device_id' => 'ESP8266_DUPLICATE',
            'name' => 'Console Duplikat',
            'status' => 'active',
            'kasir_id' => $this->kasir->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['kasir_id']);
});

it('shows the user list to an admin', function () {
    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords(User::all())
        ->assertOk();
});

it('hides user management from a kasir', function () {
    $this->actingAs($this->kasir)->get('/admin/users')->assertForbidden();
});

it('lets an admin create a kasir account', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Kasir Baru',
            'email' => 'kasir.baru@skanilan.local',
            'password' => 'password123',
            'roles' => [Role::where('name', 'kasir')->firstOrFail()->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'kasir.baru@skanilan.local')->firstOrFail();

    expect($user->hasRole('kasir'))->toBeTrue()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
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
        ->assertCanNotSeeTableRecords([$foreign])
        ->assertTableBulkActionDoesNotExist('delete');
});

it('lets an admin clear transaction history', function () {
    $this->actingAs($this->admin);

    $transaction = Transaction::create([
        'device_id' => $this->kasir->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
    ]);

    Livewire::test(ListTransactions::class)
        ->assertTableBulkActionVisible('delete')
        ->callTableBulkAction('delete', [$transaction]);

    expect(Transaction::find($transaction->id))->toBeNull();
});
