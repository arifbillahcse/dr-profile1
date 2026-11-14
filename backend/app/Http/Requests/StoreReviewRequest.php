<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    protected $errorBag = 'review';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'workplace' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'consent' => ['accepted'],
            'contact_fax' => ['prohibited'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('public.home').'#reviews';
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please agree to publish your name and review before submitting.',
            'contact_fax.prohibited' => 'Unable to accept this submission. Please leave the extra field empty.',
        ];
    }
}
