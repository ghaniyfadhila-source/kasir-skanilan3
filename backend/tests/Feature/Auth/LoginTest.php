<?php

use App\Filament\Pages\KasirPos;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('redirects the legacy login page to the panel login', function () {
    $this->get('/login')->assertRedirect('/admin/login');
});

it('renders the single panel login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('rejects invalid credentials', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@skanilan.local',
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasErrors('data.email');

    $this->assertGuest();
});

it('logs in admin and redirects to the admin dashboard', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@skanilan.local',
            'password' => 'password123',
        ])
        ->call('authenticate')
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $this->assertAuthenticated();
});

it('logs in kasir and redirects to the panel POS', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'kasir@skanilan.local',
            'password' => 'password123',
        ])
        ->call('authenticate')
        ->assertRedirect(KasirPos::getUrl());

    $this->assertAuthenticated();
});

it('redirects guests from the root URL to the panel login', function () {
    $this->get('/')->assertRedirect('/admin/login');
});

it('returns to the panel login after logging out from Filament', function () {
    $this->actingAs(User::where('email', 'admin@skanilan.local')->firstOrFail())
        ->post(route('filament.admin.auth.logout'))
        ->assertRedirect('/admin/login');

    $this->assertGuest();
});

it('returns to the panel login after logging out from Breeze', function () {
    $this->actingAs(User::where('email', 'admin@skanilan.local')->firstOrFail())
        ->post(route('logout'))
        ->assertRedirect('/admin/login');

    $this->assertGuest();
});
