<?php

namespace App\Http\Requests\Admin;

use App\Services\InlineImageUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        $image = ['nullable', 'integer', Rule::exists('media_assets', 'id')->where('disk', 'public')];

        return [
            'profile' => ['required', 'array:name,designation,specialty,trusted_patients,credentials_text,summary,biography,introduction_youtube_id,portrait_image_id,first_image_id,second_image_id,tall_image_id'],
            'profile.name' => ['required', 'string', 'max:255'],
            'profile.designation' => ['nullable', 'string', 'max:255'],
            'profile.specialty' => ['nullable', 'string', 'max:255'],
            'profile.trusted_patients' => ['nullable', 'string', 'max:50'],
            'profile.credentials_text' => ['nullable', 'string', 'max:10000'],
            'profile.summary' => ['nullable', 'string', 'max:5000'],
            'profile.biography' => ['nullable', 'string', 'max:100000'],
            'profile.introduction_youtube_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{11}$/'],
            'profile.portrait_image_id' => $image,
            'profile.first_image_id' => $image,
            'profile.second_image_id' => $image,
            'profile.tall_image_id' => $image,
            'site' => ['required', 'array:site_title,logo_image_id,favicon_image_id,social_image_id,phone,additional_phones_text,email,address,whatsapp_number,social_links,seo_title,seo_description,seo_keywords'],
            'site.site_title' => ['required', 'string', 'max:255'],
            'site.logo_image_id' => $image,
            'site.favicon_image_id' => $image,
            'site.social_image_id' => $image,
            'site.phone' => ['nullable', 'string', 'max:50'],
            'site.additional_phones_text' => ['nullable', 'string', 'max:2000'],
            'site.email' => ['nullable', 'email', 'max:255'],
            'site.address' => ['nullable', 'string', 'max:5000'],
            'site.whatsapp_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'site.social_links' => ['nullable', 'array:facebook,instagram,linkedin,youtube'],
            'site.social_links.*' => ['nullable', 'string', 'url:https', 'max:2000'],
            'site.seo_title' => ['nullable', 'string', 'max:255'],
            'site.seo_description' => ['nullable', 'string', 'max:2000'],
            'site.seo_keywords' => ['nullable', 'string', 'max:2000'],
            ...InlineImageUploads::rules(InlineImageUploads::settingsFields()),
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('profile.introduction_youtube_id');
        if (! is_string($value) || preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
            return;
        }

        $url = parse_url(trim($value));
        if (! is_array($url) || ! in_array(strtolower($url['scheme'] ?? ''), ['http', 'https'], true)) {
            return;
        }

        $host = strtolower($url['host'] ?? '');
        $id = null;
        if ($host === 'youtu.be') {
            $id = trim($url['path'] ?? '', '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            parse_str($url['query'] ?? '', $query);
            if (($url['path'] ?? '') === '/watch') {
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})/?$~', $url['path'] ?? '', $matches)) {
                $id = $matches[1];
            }
        }

        if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            $profile = $this->input('profile', []);
            $profile['introduction_youtube_id'] = $id;
            $this->merge(['profile' => $profile]);
        }
    }

    public function messages(): array
    {
        return [
            'profile.introduction_youtube_id.regex' => 'Enter a valid YouTube video ID or YouTube link.',
            'site.whatsapp_number.regex' => 'Enter a WhatsApp number with 7–15 digits and an optional leading +.',
            'site.social_links.*.url' => 'Social links must be complete HTTPS URLs.',
        ];
    }
}
