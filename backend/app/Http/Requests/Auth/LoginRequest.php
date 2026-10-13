<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! auth()->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! $this->hasTooManyLoginAttempts()) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $this->getRateLimiter()->availableIn(),
            ]),
        ]);
    }

    public function hasTooManyLoginAttempts(): bool
    {
        return $this->getRateLimiter()->tooManyAttempts($this->getThrottleKey());
    }

    public function getRateLimiter()
    {
        return app('limiter');
    }

    public function getThrottleKey(): string
    {
        return 'login:'.$this->ip();
    }
}
