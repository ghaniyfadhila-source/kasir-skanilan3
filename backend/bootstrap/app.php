<?php

use App\Http\Middleware\AuthenticateApiCaller;
use App\Http\Middleware\AuthenticateDevice;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // NOTE: the "web" middleware group is already defined in app/Http/Kernel.php.
        // Appending EncryptCookies/StartSession/VerifyCsrfToken here made them run
        // twice per request, which corrupted the session cookie and produced 419s.
        //
        // The "api" group, however, only carries SubstituteBindings by default,
        // so a browser session would never start there. The kasir POS polls
        // /api/transactions/{id}/status with fetch(), which needs the session -
        // without this the poll answered 401 and the cashier saw no status.
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'auth.device' => AuthenticateDevice::class,
            'auth.api' => AuthenticateApiCaller::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
