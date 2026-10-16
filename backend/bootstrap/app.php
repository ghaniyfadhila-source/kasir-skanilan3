<?php

use App\Http\Middleware\AuthenticateDevice;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $middleware->alias([
            'auth.device' => AuthenticateDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
