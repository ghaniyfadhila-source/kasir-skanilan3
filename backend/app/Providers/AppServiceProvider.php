<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->alias(RateLimiter::class, 'limiter');
    }

    public function boot(): void
    {
        //
    }
}
