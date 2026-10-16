<?php

use App\Filament\Resources\RfidCardResource\Pages\ListRfidCards;
use App\Models\RfidCard;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@skanilan.local')->first());
});

it('blocks a card through the table action', function () {
    $card = RfidCard::firstOrFail();

    expect($card->is_active)->toBeTrue();

    Livewire::test(ListRfidCards::class)
        ->mountTableAction('block', $card)
        ->callMountedTableAction();

    expect($card->fresh()->is_active)->toBeFalse();
});

it('offers the unblock action only for inactive cards', function () {
    $card = RfidCard::firstOrFail();

    Livewire::test(ListRfidCards::class)
        ->assertTableActionHidden('unblock', $card);

    Livewire::test(ListRfidCards::class)
        ->mountTableAction('block', $card)
        ->callMountedTableAction();

    // Re-read the record: the action visibility is evaluated against fresh state.
    Livewire::test(ListRfidCards::class)
        ->assertTableActionVisible('unblock', $card->fresh());
});

it('tops up a card balance through the table action', function () {
    $card = RfidCard::firstOrFail();
    $before = (float) $card->balance;

    Livewire::test(ListRfidCards::class)
        ->callTableAction('topup', $card, data: ['amount' => 10000]);

    expect((float) $card->fresh()->balance)->toBe($before + 10000);
});
