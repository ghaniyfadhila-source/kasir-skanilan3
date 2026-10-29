<?php

use App\Filament\Pages\KasirPos;
use App\Livewire\Kasir\KasirPos as KasirPosComponent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->admin = User::where('email', 'admin@skanilan.local')->firstOrFail();
    $this->kasir = User::where('email', 'kasir@skanilan.local')->firstOrFail();
});

it('shows the POS page inside the panel for a kasir', function () {
    $this->actingAs($this->kasir)
        ->get(KasirPos::getUrl())
        ->assertOk()
        ->assertSee('Kasir Cashless')
        ->assertSee('Nasi Goreng')
        ->assertSee('Console Kasir 1')
        ->assertSee('data-kasir-pos', false);
});

it('hides the legacy POS header in embedded mode', function () {
    $this->actingAs($this->kasir)
        ->get(KasirPos::getUrl())
        ->assertOk()
        ->assertDontSee('logout-button', false);
});

it('forbids an admin from opening the POS page', function () {
    $this->actingAs($this->admin)
        ->get(KasirPos::getUrl())
        ->assertForbidden();
});

it('redirects a guest from the POS page to the panel login', function () {
    $this->get(KasirPos::getUrl())
        ->assertRedirect('/admin/login');
});

it('redirects the legacy POS URL to the panel page', function () {
    $this->actingAs($this->kasir)
        ->get('/kasir/pos')
        ->assertRedirect(KasirPos::getUrl());
});

it('sends a signed in kasir from the root URL to the panel POS', function () {
    $this->actingAs($this->kasir)
        ->get('/')
        ->assertRedirect(KasirPos::getUrl());
});

it('sends a signed in admin from the root URL to the dashboard', function () {
    $this->actingAs($this->admin)
        ->get('/')
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('marks the POS component as embedded when rendered by the page', function () {
    Livewire::actingAs($this->kasir)
        ->test(KasirPosComponent::class, ['embedded' => true])
        ->assertSet('embedded', true)
        ->assertDontSee('logout-button', false);
});
