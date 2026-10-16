<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the transaction API, which two very different clients use:
 *
 *  - the kasir POS polls GET /api/transactions/{id}/status from the browser, so
 *    an ordinary logged in session has to be enough;
 *  - a console could equally ask with its bearer token.
 *
 * Either one resolves a device, which is merged into the request so the
 * controller can restrict the response to that console's own transactions.
 * A device token does not grant access to another device's transactions.
 */
class AuthenticateApiCaller
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = $this->deviceFromToken($request);

        if (! $device) {
            $user = $request->user();

            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // An admin has no console of their own; they may look at any
            // transaction. A kasir is limited to the console they operate.
            $device = $user->isAdmin() ? null : $user->device;
        }

        $request->merge([
            'authenticated_device' => $device,
            'authenticated_user' => $request->user(),
        ]);

        return $next($request);
    }

    protected function deviceFromToken(Request $request): ?Device
    {
        $header = $request->header('Authorization');

        if (! $header || ! Str::startsWith($header, 'Bearer ')) {
            return null;
        }

        return Device::where('api_token', Str::after($header, 'Bearer '))->first();
    }
}
