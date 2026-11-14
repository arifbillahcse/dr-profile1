<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\DoctorProfile;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class WebsiteImporter
{
    private array $report = [];

    private string $prefix = '';

    public function __construct(private MediaLibrary $library) {}

    public function run(bool $replaceDoctor = false): array
    {
        $this->report = ['created' => 0, 'kept' => 0, 'skipped_modules' => []];
        $manifest = json_decode(file_get_contents(config('website.manifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->prefix = isset($manifest['identity']) ? $manifest['identity'].':' : '';
        if (! $replaceDoctor && $this->prefix !== '' && ! $this->imported('profile:primary')
            && DoctorProfile::where('key', 'primary')->where('name', '!=', $manifest['profile']['data']['name'])->exists()) {
            throw new RuntimeException('A different doctor profile exists. Run php artisan website:doctor to archive the previous content and apply the new doctor.');
        }
        foreach ([$manifest['profile'], $manifest['site'], ...array_merge(...array_values($manifest['modules']))] as $entry) {
            foreach ($entry['media'] ?? [] as $filename) {
                if ($filename !== null) {
                    $this->source($filename);
                }
            }
        }

        if ($replaceDoctor) {
            foreach (array_keys($manifest['modules']) as $module) {
                $class = config('admin.modules')[$module]['model'];
                $class::query()->delete();
            }
            Review::query()->delete();
        }

        $this->singleton('profile:primary', DoctorProfile::class, $manifest['profile'], $replaceDoctor);
        $this->singleton('site:primary', SiteSetting::class, $manifest['site'], $replaceDoctor);

        foreach ($manifest['modules'] as $module => $entries) {
            if (! array_key_exists($module, config('content_editor.modules'))) {
                throw new RuntimeException('Unrecognized import module.');
            }
            $class = config('admin.modules')[$module]['model'];
            $history = DB::table('website_imports')->where('model', $class)->exists();
            if (! $replaceDoctor && ! $history && $class::withTrashed()->exists()) {
                $this->report['skipped_modules'][] = $module;

                continue;
            }
            foreach ($entries as $index => $entry) {
                $key = 'content:'.$module.':'.$index;
                if ($this->imported($key)) {
                    $this->report['kept']++;

                    continue;
                }
                $data = $entry['data'];
                $schedules = $data['schedules'] ?? [];
                unset($data['schedules']);
                $data = [...$data, ...$this->mediaIds($entry), 'sort_order' => ($index + 1) * 10, 'is_active' => true];
                DB::transaction(function () use ($key, $class, $data, $module, $schedules): void {
                    $record = $class::create($data);
                    if ($module === 'chamber') {
                        $record->schedules()->createMany($schedules);
                    }
                    $this->remember($key, $record);
                });
                $this->report['created']++;
            }
        }

        if (DB::table('website_imports')->where('model', Review::class)->exists() || ! Review::withTrashed()->exists()) {
            foreach ($manifest['reviews'] as $index => $entry) {
                $key = 'review:'.$index;
                if ($this->imported($key)) {
                    continue;
                }
                DB::transaction(function () use ($key, $entry): void {
                    $review = new Review;
                    $review->forceFill([...$entry['data'], 'status' => ReviewStatus::Approved, 'approved_at' => now()])->save();
                    $this->remember($key, $review);
                });
                $this->report['created']++;
            }
        }

        return $this->report;
    }

    private function singleton(string $key, string $class, array $entry, bool $replaceDoctor = false): void
    {
        if ($this->imported($key)) {
            return;
        }
        $record = $class::firstOrNew(['key' => 'primary']);
        if ($replaceDoctor) {
            $record->fill([...$entry['data'], ...$this->mediaIds($entry)])->save();
            $this->remember($key, $record);

            return;
        }
        $data = [];
        foreach ($entry['data'] as $column => $value) {
            if ($column === 'social_links') {
                $links = $record->getAttribute($column) ?? [];
                foreach ($value as $network => $url) {
                    if (blank($links[$network] ?? null)) {
                        $links[$network] = $url;
                    }
                }
                $data[$column] = $links;
            } elseif (blank($record->getAttribute($column))) {
                $data[$column] = $value;
            }
        }
        foreach ($entry['media'] ?? [] as $column => $filename) {
            if (blank($record->getAttribute($column)) && $filename) {
                $data[$column] = $this->media($filename)?->id;
            }
        }
        DB::transaction(function () use ($record, $data, $key): void {
            $record->fill($data)->save();
            $this->remember($key, $record);
        });
    }

    private function mediaIds(array $entry): array
    {
        $ids = [];
        foreach ($entry['media'] ?? [] as $column => $filename) {
            $ids[$column] = $filename ? $this->media($filename)?->id : null;
        }

        return $ids;
    }

    private function media(string $filename): ?MediaAsset
    {
        $key = 'media:'.$filename;
        $mapping = DB::table('website_imports')->where('key', $this->prefix.$key)->first();
        if ($mapping) {
            return MediaAsset::find($mapping->record_id);
        }
        $created = null;
        try {
            return DB::transaction(function () use ($filename, $key, &$created): MediaAsset {
                $source = $this->source($filename);
                $created = $this->library->store(new UploadedFile($source, $filename, mime_content_type($source), test: true), null);
                $this->remember($key, $created);

                return $created;
            });
        } catch (Throwable $exception) {
            if ($created) {
                Storage::disk('public')->delete($created->path);
            }
            throw $exception;
        }
    }

    private function source(string $filename): string
    {
        $root = realpath(config('website.seed_assets'));
        $path = realpath(config('website.seed_assets').DIRECTORY_SEPARATOR.$filename);
        if (! preg_match('/^[A-Za-z0-9._-]+$/D', $filename) || ! $root || ! $path || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            throw new RuntimeException('A required local import image is missing or invalid.');
        }

        return $path;
    }

    private function imported(string $key): bool
    {
        return DB::table('website_imports')->where('key', $this->prefix.$key)->exists();
    }

    private function remember(string $key, Model $model): void
    {
        DB::table('website_imports')->insert(['key' => $this->prefix.$key, 'model' => $model::class, 'record_id' => $model->getKey(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
