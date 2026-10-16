<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // LoginRequest resolves the limiter through the 'limiter' alias.
        $this->app->alias(RateLimiter::class, 'limiter');
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
