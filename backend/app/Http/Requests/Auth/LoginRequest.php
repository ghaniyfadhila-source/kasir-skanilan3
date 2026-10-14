<?php

namespace App\Http\Requests\Auth;

use Illuminate\Cache\RateLimiter;
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
            $this->getRateLimiter()->hit($this->getThrottleKey(), 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->getRateLimiter()->clear($this->getThrottleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! $this->hasTooManyLoginAttempts()) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $this->getRateLimiter()->availableIn($this->getThrottleKey()),
                'minutes' => ceil($this->getRateLimiter()->availableIn($this->getThrottleKey()) / 60),
            ]),
        ]);
    }

    public function hasTooManyLoginAttempts(): bool
    {
        return $this->getRateLimiter()->tooManyAttempts($this->getThrottleKey(), 5);
    }

    public function getRateLimiter(): RateLimiter
    {
        return app('limiter');
    }

    public function getThrottleKey(): string
    {
        return 'login:'.$this->ip();
    }
}
