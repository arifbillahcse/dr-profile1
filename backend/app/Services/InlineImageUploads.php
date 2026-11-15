<?php

namespace App\Services;

use App\Models\DoctorProfile;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Throwable;

class InlineImageUploads
{
    public function __construct(private MediaLibrary $library) {}

    public static function contentFields(string $module): array
    {
        return array_keys(array_filter(config('content_editor.modules.'.$module.'.fields', []), fn (array $field): bool => ($field['type'] ?? null) === 'image'));
    }

    public static function settingsFields(): array
    {
        $fields = [];
        foreach (['profile' => DoctorProfile::class, 'site' => SiteSetting::class] as $group => $model) {
            foreach (config('media.references') as $reference) {
                if ($reference['model'] === $model) {
                    foreach ($reference['columns'] as $column) {
                        $fields[] = $group.'.'.$column;
                    }
                }
            }
        }

        return $fields;
    }

    public static function fileRules(): array
    {
        return [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5120)->dimensions(Rule::dimensions()->maxWidth(4096)->maxHeight(4096)), 'extensions:jpg,jpeg,png,webp'];
    }

    public static function rules(array $fields): array
    {
        if ($fields === []) {
            return ['uploads' => ['prohibited']];
        }
        $rules = [];
        $groups = [];
        foreach ($fields as $field) {
            $parent = 'uploads';
            foreach (explode('.', $field) as $part) {
                $groups[$parent][] = $part;
                $parent .= '.'.$part;
            }
            $rules['uploads.'.$field] = ['nullable', ...self::fileRules()];
        }
        foreach ($groups as $parent => $keys) {
            $rules[$parent] = ['sometimes', 'array:'.implode(',', array_unique($keys))];
        }

        return $rules;
    }

    public function save(Request $request, array $data, array $fields, Closure $persist): mixed
    {
        unset($data['uploads']);
        $created = [];
        try {
            return DB::transaction(function () use ($request, $data, $fields, $persist, &$created): mixed {
                foreach ($fields as $field) {
                    if (! $request->hasFile('uploads.'.$field)) {
                        continue;
                    }
                    try {
                        $media = $this->library->store($request->file('uploads.'.$field), null, $request->user());
                    } catch (ValidationException $exception) {
                        throw ValidationException::withMessages(['uploads.'.$field => Arr::flatten($exception->errors())]);
                    }
                    $created[] = $media;
                    Arr::set($data, $field, $media->id);
                }

                return $persist($data);
            });
        } catch (Throwable $exception) {
            foreach ($created as $media) {
                Storage::disk($media->disk)->delete($media->path);
            }
            throw $exception;
        }
    }
}
