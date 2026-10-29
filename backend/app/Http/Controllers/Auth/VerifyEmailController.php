<?php

namespace App\Http\Controllers\Auth;

use App\Filament\Pages\KasirPos;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->intended(KasirPos::getUrl().'?verified=1');
    }
}
