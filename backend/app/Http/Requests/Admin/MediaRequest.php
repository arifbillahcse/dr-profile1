<?php

namespace App\Http\Requests\Admin;

use App\Services\InlineImageUploads;
use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'image' => [$this->isMethod('POST') ? 'required' : 'nullable', ...InlineImageUploads::fileRules()],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
