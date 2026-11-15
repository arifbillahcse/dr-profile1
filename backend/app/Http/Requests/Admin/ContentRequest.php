<?php

namespace App\Http\Requests\Admin;

use App\Services\InlineImageUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true && array_key_exists((string) $this->route('module'), config('content_editor.modules'));
    }

    public function rules(): array
    {
        $module = (string) $this->route('module');
        $definition = config('content_editor.modules')[$module] ?? [];
        $rules = ['sort_order' => ['required', 'integer', 'min:0', 'max:1000000'], 'is_active' => ['sometimes', 'boolean']];
        foreach ($definition['fields'] ?? [] as $name => $field) {
            $rules[$name] = $field['rules'] ?? [];
            if (($field['type'] ?? '') === 'image') {
                if ($this->hasFile('uploads.'.$name)) {
                    $rules[$name] = array_values(array_diff($rules[$name], ['required']));
                    $rules[$name][] = 'nullable';
                }
                $rules[$name][] = Rule::exists('media_assets', 'id')->where('disk', 'public');
            }
            if (($field['type'] ?? '') === 'icon') {
                $rules[$name] = ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+(?: [a-z0-9-]+)*$/'];
            }
        }
        if ($module === 'service') {
            $rules['slug'][] = Rule::unique('services', 'slug')->ignore((int) $this->route('record'));
        }
        if ($module === 'chamber') {
            $rules += [
                'schedules_present' => ['sometimes', 'boolean'],
                'schedules' => ['sometimes', 'array', 'max:28'],
                'schedules.*' => ['required', 'array:day_of_week,opens_at,closes_at,is_active'],
                'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
                'schedules.*.opens_at' => ['required', 'date_format:H:i'],
                'schedules.*.closes_at' => ['required', 'date_format:H:i', 'after:schedules.*.opens_at'],
                'schedules.*.is_active' => ['required', 'boolean'],
            ];
        }

        return [...$rules, ...InlineImageUploads::rules(InlineImageUploads::contentFields($module))];
    }

    protected function prepareForValidation(): void
    {
        $module = $this->route('module');
        if ($module === 'service' && blank($this->input('slug')) && is_string($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name')) ?: 'service-'.Str::lower(Str::random(10))]);
        }
        if ($module === 'youtube' && is_string($this->input('youtube_id'))) {
            $this->merge(['youtube_id' => $this->youtubeId($this->input('youtube_id'))]);
        }
        if ($module === 'experience' && $this->boolean('is_current') && ! array_key_exists('ended_on', $this->all())) {
            $this->merge(['ended_on' => null]);
        }
        if ($module === 'chamber' && $this->has('schedules_present') && ! $this->has('schedules')) {
            $this->merge(['schedules' => []]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->route('module') !== 'chamber') {
                return;
            }
            $seen = [];
            $active = [];
            foreach ($this->input('schedules', []) as $index => $slot) {
                $key = $slot['day_of_week'].'|'.$slot['opens_at'];
                if (isset($seen[$key])) {
                    $validator->errors()->add('schedules.'.$index.'.opens_at', 'This weekday already has a slot with the same opening time.');
                }
                $seen[$key] = true;
                if (! $slot['is_active']) {
                    continue;
                }
                foreach ($active[$slot['day_of_week']] ?? [] as $other) {
                    if ($slot['opens_at'] < $other['closes_at'] && $slot['closes_at'] > $other['opens_at']) {
                        $validator->errors()->add('schedules.'.$index.'.opens_at', 'Active time slots on the same weekday must not overlap.');
                    }
                }
                $active[$slot['day_of_week']][] = $slot;
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (config('content_editor.modules')[(string) $this->route('module')]['fields'] ?? [] as $key => $field) {
            $attributes[$key] = strtolower($field['label']);
        }

        return $attributes;
    }

    private function youtubeId(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
            return $value;
        }
        $url = parse_url($value);
        if (! is_array($url) || ! in_array(strtolower($url['scheme'] ?? ''), ['http', 'https'], true)) {
            return $value;
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

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : $value;
    }
}
