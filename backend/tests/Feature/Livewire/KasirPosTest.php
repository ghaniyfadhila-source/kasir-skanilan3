<?php

use App\Livewire\Kasir\KasirPos;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();
    $this->device = $this->kasir->device;
    $this->nasiGoreng = Product::where('name', 'Nasi Goreng')->firstOrFail();
    $this->airMineral = Product::where('name', 'Air Mineral')->firstOrFail();
});

it('loads only active products that still have stock', function () {
    Product::create([
        'kasir_id' => $this->kasir->id,
        'name' => 'Stok Habis',
        'price' => 1000,
        'stock' => 0,
        'is_active' => true,
    ]);

    Product::create([
        'kasir_id' => $this->kasir->id,
        'name' => 'Produk Nonaktif',
        'price' => 1000,
        'stock' => 10,
        'is_active' => false,
    ]);

    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class);
    $names = collect($component->get('products'))->pluck('name');

    expect($names)->toContain('Nasi Goreng')
        ->not->toContain('Stok Habis')
        ->not->toContain('Produk Nonaktif');
});

it('shows only the products of the logged in kasir', function () {
    $other = User::create([
        'name' => 'Kasir Lain',
        'email' => 'kasir.lain@skanilan.local',
        'password' => 'password123',
    ]);

    Product::create([
        'kasir_id' => $other->id,
        'name' => 'Milik Kasir Lain',
        'price' => 1000,
        'stock' => 10,
        'is_active' => true,
    ]);

    $names = collect(Livewire::actingAs($this->kasir)->test(KasirPos::class)->get('products'))
        ->pluck('name');

    expect($names)->not->toContain('Milik Kasir Lain');
});

it('adds a product to the cart and calculates the total', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->assertSet('cart.0.name', 'Nasi Goreng')
        ->assertSet('cart.0.qty', 1)
        ->assertSet('cart.0.subtotal', 15000);

    expect($component->get('total'))->toEqual(15000.0);
});

it('ignores a product that is not on the menu', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', 99999);

    expect($component->get('cart'))->toBeEmpty()
        ->and($component->get('total'))->toEqual(0);
});

it('increases the quantity when the same product is added again', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('addToCart', $this->nasiGoreng->id);

    expect($component->get('cart'))->toHaveCount(1)
        ->and($component->get('cart.0.qty'))->toEqual(2)
        ->and($component->get('cart.0.subtotal'))->toEqual(30000.0)
        ->and($component->get('total'))->toEqual(30000.0);
});

it('refuses to add more items than the remaining stock', function () {
    $product = Product::create([
        'kasir_id' => $this->kasir->id,
        'name' => 'Sisa Satu',
        'price' => 4000,
        'stock' => 1,
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id);

    expect($component->get('cart.0.qty'))->toEqual(1)
        ->and($component->get('total'))->toEqual(4000.0);
});

it('updates the quantity and recalculates the subtotal', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('updateQty', 0, 3);

    expect($component->get('cart.0.qty'))->toEqual(3)
        ->and($component->get('cart.0.subtotal'))->toEqual(45000.0)
        ->and($component->get('total'))->toEqual(45000.0);
});

it('caps the quantity at the available stock', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('updateQty', 0, 999);

    // Nasi Goreng is seeded with 50 in stock.
    expect($component->get('cart.0.qty'))->toEqual($this->nasiGoreng->stock);
});

it('keeps the quantity at one when a lower value is requested', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('updateQty', 0, 0);

    expect($component->get('cart.0.qty'))->toEqual(1);
});

it('removes an item and recalculates the total', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('addToCart', $this->airMineral->id)
        ->call('removeFromCart', 0);

    expect($component->get('cart'))->toHaveCount(1)
        ->and($component->get('cart.0.name'))->toEqual('Air Mineral')
        ->and($component->get('total'))->toEqual(3000.0);
});

it('clears the whole cart', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('addToCart', $this->airMineral->id)
        ->call('clearCart');

    expect($component->get('cart'))->toBeEmpty()
        ->and($component->get('total'))->toEqual(0);
});

it('does not create a transaction when the cart is empty', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('checkout')
        ->assertDispatched('notify');

    expect(Transaction::count())->toBe(0)
        ->and($component->get('status'))->toEqual('idle');
});

it('creates a pending transaction for the kasir device on checkout', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout');

    $transaction = Transaction::sole();

    expect($transaction->device_id)->toEqual($this->device->id)
        ->and($transaction->status)->toEqual('pending')
        ->and($transaction->isPending())->toBeTrue()
        ->and((float) $transaction->total)->toEqual(30000.0)
        ->and($transaction->rfid_card_id)->toBeNull()
        ->and($transaction->items)->toHaveCount(1)
        ->and($transaction->items[0]['product_id'])->toEqual($this->nasiGoreng->id)
        ->and($transaction->items[0]['qty'])->toEqual(2)
        ->and($component->get('status'))->toEqual('processing')
        ->and($component->get('transactionId'))->toEqual($transaction->id)
        ->and($component->get('message'))->toEqual('Menunggu pembeli tap kartu...');
});

it('expires the pending transaction after the tap window', function () {
    Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout');

    $transaction = Transaction::sole();

    expect($transaction->expires_at)->not->toBeNull()
        ->and($transaction->isExpired())->toBeFalse();
});

it('fails the checkout when the kasir has no device', function () {
    $this->device->delete();

    // Re-fetch: the instance in $this->kasir still holds the deleted relation.
    $kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();

    $component = Livewire::actingAs($kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout');

    expect($component->get('status'))->toEqual('failed')
        ->and($component->get('message'))->toEqual('Device kasir belum terdaftar')
        ->and(Transaction::count())->toBe(0);
});

it('cancels a pending transaction and clears the transaction state', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout')
        ->call('cancelTransaction');

    expect(Transaction::sole()->status)->toEqual('cancelled')
        ->and($component->get('status'))->toEqual('idle')
        ->and($component->get('transactionId'))->toBeNull()
        ->and($component->get('message'))->toEqual('');
});

it('does nothing when cancelling without an active transaction', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('cancelTransaction');

    expect(Transaction::count())->toBe(0)
        ->and($component->get('status'))->toEqual('idle');
});

it('applies a successful tap result and empties the cart', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout')
        ->call('handlePollResult', [
            'status' => 'success',
            'balance_before' => 50000,
            'balance_after' => 35000,
            'card' => ['uid' => 'A1B2C3D4', 'user_name' => 'Budi Santoso'],
        ])
        ->assertDispatched('auto-reset');

    expect($component->get('status'))->toEqual('success')
        ->and($component->get('message'))->toEqual('Transaksi berhasil!')
        ->and((float) $component->get('balanceBefore'))->toEqual(50000.0)
        ->and((float) $component->get('balanceAfter'))->toEqual(35000.0)
        ->and($component->get('cardInfo.user_name'))->toEqual('Budi Santoso')
        ->and($component->get('cart'))->toBeEmpty()
        ->and($component->get('total'))->toEqual(0);
});

it('applies a failed tap result and keeps the cart for a retry', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout')
        ->call('handlePollResult', [
            'status' => 'failed',
            'message' => 'Saldo tidak mencukupi',
        ]);

    expect($component->get('status'))->toEqual('failed')
        ->and($component->get('message'))->toEqual('Saldo tidak mencukupi')
        ->and($component->get('cart'))->toHaveCount(1);
});

it('reports a cancelled tap result as a timeout', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout')
        ->call('handlePollResult', ['status' => 'cancelled']);

    expect($component->get('status'))->toEqual('failed')
        ->and($component->get('message'))->toEqual('Transaksi dibatalkan (timeout)');
});

it('keeps waiting while the transaction is still pending', function () {
    $component = Livewire::actingAs($this->kasir)->test(KasirPos::class)
        ->call('addToCart', $this->nasiGoreng->id)
        ->call('checkout')
        ->call('handlePollResult', ['status' => 'pending']);

    expect($component->get('status'))->toEqual('processing');
});
