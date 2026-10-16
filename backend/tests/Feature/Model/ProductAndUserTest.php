<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin = User::where('email', 'admin@skanilan.local')->firstOrFail();
    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();
    $this->product = Product::where('name', 'Nasi Goreng')->firstOrFail();
});

it('casts price to a decimal, stock to an integer and is_active to a boolean', function () {
    expect((string) $this->product->price)->toEqual('15000.00')
        ->and($this->product->stock)->toBeInt()
        ->and($this->product->is_active)->toBeTrue();
});

it('is mass assignable through its fillable attributes', function () {
    $product = Product::create([
        'kasir_id' => $this->kasir->id,
        'name' => 'Es Campur',
        'price' => 9000,
        'stock' => 25,
        'is_active' => true,
        'category' => 'Minuman',
    ]);

    expect($product->exists)->toBeTrue()
        ->and($product->name)->toEqual('Es Campur')
        ->and($product->category)->toEqual('Minuman')
        ->and((float) $product->price)->toEqual(9000.0);
});

it('belongs to the kasir who owns it', function () {
    expect($this->product->kasir)->toBeInstanceOf(User::class)
        ->and($this->product->kasir->id)->toEqual($this->kasir->id)
        ->and($this->product->kasir->isKasir())->toBeTrue();
});

it('lists the products of a kasir through the relation', function () {
    Product::create([
        'kasir_id' => $this->kasir->id,
        'name' => 'Produk Tambahan',
        'price' => 1000,
        'stock' => 5,
        'is_active' => true,
    ]);

    expect($this->kasir->products()->count())->toEqual(9)
        ->and($this->admin->products()->count())->toEqual(0);
});

it('tells an admin and a kasir apart', function () {
    expect($this->admin->isAdmin())->toBeTrue()
        ->and($this->admin->isKasir())->toBeFalse()
        ->and($this->kasir->isKasir())->toBeTrue()
        ->and($this->kasir->isAdmin())->toBeFalse();
});

it('resolves the console the kasir operates through kasir_id', function () {
    expect($this->kasir->device)->not->toBeNull()
        ->and($this->kasir->device->device_id)->toEqual('ESP8266_DEMO_001')
        ->and($this->kasir->device->status)->toEqual('active')
        ->and($this->admin->device)->toBeNull();
});

it('keeps users.device_id pointing at the same console', function () {
    expect($this->kasir->device_id)->toEqual($this->kasir->device->id)
        ->and($this->admin->device_id)->toBeNull();
});

it('hashes the password and hides it from array output', function () {
    expect($this->kasir->password)->not->toEqual('password123')
        ->and(Hash::check('password123', $this->kasir->password))->toBeTrue()
        ->and($this->kasir->toArray())->not->toHaveKey('password')
        ->and($this->kasir->toArray())->not->toHaveKey('remember_token');
});
