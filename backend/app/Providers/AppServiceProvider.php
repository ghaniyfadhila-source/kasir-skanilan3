<?php

namespace App\Providers;

use App\Http\Responses\FilamentLoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as FilamentLoginResponseContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            FilamentLoginResponseContract::class,
            FilamentLoginResponse::class,
        );
    }

    public function boot(): void
    {
        // A console polls for the pending transaction and sends a heartbeat, so
        // its budget is generous but still bounded: the API is reachable from
        // the school network.
        RateLimiterFacade::for('api-device', function (Request $request) {
            $device = $request->input('authenticated_device');

            return Limit::perMinute(120)->by($device?->api_token ?? $request->ip());
        });

        // The POS polls the transaction status every two seconds while a buyer
        // taps a card, so this is a per-console budget rather than a small one.
        RateLimiterFacade::for('api-transaction', function (Request $request) {
            $device = $request->input('authenticated_device');
            $user = $request->user();

            return Limit::perMinute(300)->by($device?->api_token ?? $user?->id ?? $request->ip());
        });
    }
}
