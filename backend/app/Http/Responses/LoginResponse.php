<?php

namespace App\Http\Responses;

use App\Filament\Pages\KasirPos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginResponse
{
    public function toResponse(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return redirect()->intended(route('filament.admin.pages.dashboard'));
        }

        return redirect()->intended(KasirPos::getUrl());
    }
}
