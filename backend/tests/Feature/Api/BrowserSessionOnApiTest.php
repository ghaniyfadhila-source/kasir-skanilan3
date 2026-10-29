<?php

use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();

    $this->transaction = Transaction::create([
        'device_id' => $this->kasir->device->id,
        'total' => 15000,
        'status' => 'pending',
        'items' => [['product_id' => 1, 'name' => 'Nasi Goreng', 'qty' => 1, 'price' => 15000, 'subtotal' => 15000]],
        'balance_before' => 0,
        'balance_after' => 0,
        'expires_at' => now()->addSeconds(30),
    ]);
});

/*
 * The POS polls /api/transactions/{id}/status from fetch(). That only works
 * when the api middleware group starts a session and Sanctum recognises the
 * host as stateful - both of which broke silently before, answering 401 while
 * the cashier only saw "waiting for tap" forever.
 */

it('starts a session on api routes', function () {
    expect(app('router')->getMiddlewareGroups()['api'])
        ->toContain(EnsureFrontendRequestsAreStateful::class);
});

it('treats the application host as a stateful domain', function () {
    $url = parse_url(config('app.url'));
    $target = $url['host'].(isset($url['port']) ? ':'.$url['port'] : '');

    expect(config('sanctum.stateful'))->toContain($target);
});

it('lets the POS browser poll the status with its login session', function () {
    $this->post('/login', [
        'email' => 'kasir@skanilan.local',
        'password' => 'password123',
    ]);

    $this->withHeader('referer', config('app.url').'/admin/pos')
        ->getJson("/api/transactions/{$this->transaction->id}/status")
        ->assertOk()
        ->assertJson([
            'success' => true,
            'transaction' => ['id' => $this->transaction->id, 'status' => 'pending'],
        ]);
});

it('still rejects the same call without a login', function () {
    $this->withHeader('referer', config('app.url').'/admin/pos')
        ->getJson("/api/transactions/{$this->transaction->id}/status")
        ->assertUnauthorized();
});
