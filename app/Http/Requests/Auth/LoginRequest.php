<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ];
    }

    protected function prepareForValidation(): void
    {
        RateLimiter::attempt(
            'login-' . $this->ip(),
            5,
            fn () => null,
            60
        );
    }
}
