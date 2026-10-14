<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $device = Device::where('api_token', $token)->first();

        if (! $device) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->merge(['authenticated_device' => $device]);

        return $next($request);
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! $header || ! Str::startsWith($header, 'Bearer ')) {
            return null;
        }

        return Str::after($header, 'Bearer ');
    }
}
