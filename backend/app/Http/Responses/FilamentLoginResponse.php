<?php

namespace App\Http\Responses;

use App\Filament\Pages\KasirPos;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        $default = $user?->hasRole('admin')
            ? Filament::getUrl()
            : KasirPos::getUrl();

        return redirect()->intended($default);
    }
}
