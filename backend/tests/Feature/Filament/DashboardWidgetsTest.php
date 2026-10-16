<?php

use App\Filament\Widgets\DeviceStatusGrid;
use App\Filament\Widgets\LiveTransactionFeed;
use App\Filament\Widgets\RevenueChart;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin = User::where('email', 'admin@skanilan.local')->firstOrFail();
    $this->device = User::where('email', 'kasir@skanilan.local')->firstOrFail()->device;
    $this->actingAs($this->admin);
});

it('lists every device with its kasir and online state', function () {
    $this->device->update(['last_seen_at' => now()]);

    $component = Livewire::test(DeviceStatusGrid::class);
    $rows = collect($component->get('devices'));

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['device_id'])->toEqual('ESP8266_DEMO_001')
        ->and($rows->first()['name'])->toEqual('Console Kasir 1')
        ->and($rows->first()['status'])->toEqual('active')
        ->and($rows->first()['kasir_name'])->toEqual('Kasir Demo')
        ->and($rows->first()['is_online'])->toBeTrue()
        ->and($rows->first()['last_seen_at'])->toEqual(now()->format('d/m/Y H:i'))
        ->and($rows->first()['edit_url'])->toContain('/admin/devices/');
});

it('reports a stale console as offline', function () {
    $this->device->update(['last_seen_at' => now()->subMinutes(10)]);

    $rows = collect(Livewire::test(DeviceStatusGrid::class)->get('devices'));

    expect($rows->first()['is_online'])->toBeFalse();
});

it('exposes the heading and description of the plain widgets', function () {
    $grid = Livewire::test(DeviceStatusGrid::class)->instance();
    $feed = Livewire::test(LiveTransactionFeed::class)->instance();

    expect($grid->getHeading())->toEqual('Device Status Grid')
        ->and($grid->getDescription())->toEqual('Status online/offline tiap device kasir')
        ->and($feed->getHeading())->toEqual('Live Transaksi')
        ->and($feed->getDescription())->toEqual('Real-time transaction feed');
});

it('summarises successful revenue per day for the chart', function () {
    Transaction::create([
        'device_id' => $this->device->id,
        'total' => 15000,
        'status' => 'success',
        'items' => [],
        'balance_before' => 50000,
        'balance_after' => 35000,
        'created_at' => now(),
    ]);

    // Failed and cancelled transactions must not be counted as revenue.
    Transaction::create([
        'device_id' => $this->device->id,
        'total' => 99000,
        'status' => 'failed',
        'items' => [],
        'balance_before' => 0,
        'balance_after' => 0,
        'created_at' => now(),
    ]);

    $chart = Livewire::test(RevenueChart::class);
    $data = Closure::bind(fn () => $this->getData(), $chart->instance(), RevenueChart::class)();

    expect($data['labels'])->toEqual([now()->format('d/m')])
        ->and($data['datasets'][0]['data'])->toEqual([15000.0])
        ->and($chart->assertSee('Pendapatan Harian'));
});

it('returns an empty dataset when nothing has been sold yet', function () {
    $chart = Livewire::test(RevenueChart::class);
    $data = Closure::bind(fn () => $this->getData(), $chart->instance(), RevenueChart::class)();

    expect($data['labels'])->toBeEmpty()
        ->and($data['datasets'][0]['data'])->toBeEmpty();
});

it('forwards a broadcast transaction to the browser', function () {
    $payload = ['id' => 1, 'total' => 15000];

    Livewire::test(LiveTransactionFeed::class)
        ->call('onTransactionCompleted', $payload)
        ->assertDispatched('transaction-completed', $payload);
});

it('subscribes to the admin transaction channel', function () {
    $listeners = Livewire::test(LiveTransactionFeed::class)->instance()->getListeners();

    expect($listeners)->toHaveKey('echo:private-admin.transactions,.transaction.completed')
        ->and($listeners['echo:private-admin.transactions,.transaction.completed'])
        ->toEqual('onTransactionCompleted');
});
