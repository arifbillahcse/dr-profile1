<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function authenticate(): void
    {
        $key = 'admin-login:'.hash('sha256', $this->string('email').'|'.$this->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many sign-in attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        if (! Auth::attempt([...$this->safe()->only(['email', 'password']), 'is_admin' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'The supplied credentials do not match an administrator account.']);
        }

        RateLimiter::clear($key);
    }
}
