<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\KasirPos;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_page_renders_successfully(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_forgot_password_page_renders_successfully(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    public function test_reset_password_page_renders_successfully(): void
    {
        $response = $this->get('/reset-password/sample-token');
        $response->assertStatus(200);
    }

    public function test_verify_email_prompt_renders_for_unverified_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::create([
            'name' => 'Kasir Belum Verifikasi',
            'email' => 'unverified@skanilan.local',
            'password' => 'password123',
        ]);
        $user->assignRole('kasir');

        $response = $this->actingAs($user)->get('/verify-email');
        $response->assertStatus(200);
    }

    public function test_confirm_password_page_renders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::create([
            'name' => 'Kasir Konfirmasi',
            'email' => 'konfirmasi@skanilan.local',
            'password' => 'password123',
        ]);
        $user->assignRole('kasir');

        $response = $this->actingAs($user)->get('/confirm-password');
        $response->assertStatus(200);
    }

    public function test_verify_email_link_redirects_to_panel_pos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::create([
            'name' => 'Kasir Verifikasi',
            'email' => 'verifikasi@skanilan.local',
            'password' => 'password123',
        ]);
        $user->assignRole('kasir');

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(KasirPos::getUrl().'?verified=1');

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    }
}
