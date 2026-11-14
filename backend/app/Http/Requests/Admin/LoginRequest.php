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
            'username' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9._-]{2,63}$/D', 'max:64'],
            'password' => ['required', 'string', 'max:1024'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => Str::lower(trim($this->input('username')))]);
        }
    }

    public function authenticate(): void
    {
        $key = 'admin-login:'.hash('sha256', $this->string('username').'|'.$this->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => 'Too many sign-in attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        if (! Auth::attempt([...$this->safe()->only(['username', 'password']), 'is_admin' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => 'The supplied credentials do not match an administrator account.']);
        }

        RateLimiter::clear($key);
    }
}
