<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'image' => [$this->isMethod('POST') ? 'required' : 'nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5120)->dimensions(Rule::dimensions()->maxWidth(4096)->maxHeight(4096)), 'extensions:jpg,jpeg,png,webp'],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
