<?php

use App\Filament\Pages\KasirPos;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('rejects invalid credentials with auth failed error', function () {
    $response = $this->post('/login', [
        'email' => 'admin@skanilan.local',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('throttles login after too many attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/login', [
            'email' => 'admin@skanilan.local',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'admin@skanilan.local',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan masuk');
    $this->assertGuest();
});

it('logs in admin and redirects to admin dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'admin@skanilan.local',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('filament.admin.pages.dashboard'));
    $this->assertAuthenticated();
});

it('logs in kasir and redirects to pos page', function () {
    $response = $this->post('/login', [
        'email' => 'kasir@skanilan.local',
        'password' => 'password123',
    ]);

    $response->assertRedirect(KasirPos::getUrl());
    $this->assertAuthenticated();
});
