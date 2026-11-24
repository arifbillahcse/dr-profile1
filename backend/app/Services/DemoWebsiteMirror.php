<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\ChamberSchedule;
use App\Models\DoctorProfile;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DemoWebsiteMirror
{
    public const MARKER = 'demo-reference-2026-11-24-v1:applied';

    /** @return array{applied: bool, backup: ?string} */
    public function run(): array
    {
        if (! app()->environment(['local', 'testing']) && parse_url(config('app.url'), PHP_URL_HOST) !== 'drfarzanashathi.com') {
            throw new RuntimeException('This migration targets drfarzanashathi.com only. Check APP_URL and the target project directory. The demo must not be changed.');
        }
        $manifest = json_decode(file_get_contents(config('website.demo_manifest', database_path('seeders/data/demo-reference.json'))), true, flags: JSON_THROW_ON_ERROR);
        if (DB::table('website_imports')->where('key', self::MARKER)->exists()) {
            return ['applied' => false, 'backup' => null];
        }
        $profile = DoctorProfile::where('key', 'primary')->first();
        if ($profile && $profile->name !== $manifest['profile']['data']['name']) {
            throw new RuntimeException('The target contains another doctor. No content was changed.');
        }
        $assets = $this->assets($manifest);
        $backup = $this->backup();
        $written = [];
        try {
            DB::transaction(function () use ($manifest, $assets, &$written): void {
                $media = [];
                foreach ($assets as $key => $asset) {
                    $path = 'uploads/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
                    if (! Storage::disk('public')->put($path, file_get_contents($asset['source']))) {
                        throw new RuntimeException('A reference photo could not be stored.');
                    }
                    $written[] = $path;
                    $image = MediaAsset::create([
                        'disk' => 'public', 'path' => $path, 'original_name' => $asset['file'], 'mime_type' => 'image/webp',
                        'size_bytes' => filesize($asset['source']), 'width' => $asset['width'], 'height' => $asset['height'],
                        'alt_text' => $asset['alt_text'], 'uploaded_by' => null,
                    ]);
                    $media[$key] = $image->id;
                }
                DoctorProfile::updateOrCreate(['key' => 'primary'], [...$manifest['profile']['data'], ...$this->mediaIds($manifest['profile'], $media)]);
                SiteSetting::updateOrCreate(['key' => 'primary'], [...$manifest['site']['data'], ...$this->mediaIds($manifest['site'], $media)]);
                foreach ($manifest['modules'] as $module => $entries) {
                    $class = config('admin.modules')[$module]['model'];
                    $class::published()->update(['is_active' => false]);
                    foreach ($entries as $index => $entry) {
                        $data = $entry['data'];
                        $schedules = $data['schedules'] ?? [];
                        unset($data['schedules']);
                        $record = $module === 'service' ? Service::withTrashed()->firstOrNew(['slug' => $data['slug']]) : new $class;
                        if ($record->exists && $record->trashed()) {
                            $record->restore();
                        }
                        $record->fill([...$data, ...$this->mediaIds($entry, $media), 'sort_order' => ($index + 1) * 10, 'is_active' => true])->save();
                        if ($module === 'chamber') {
                            $record->schedules()->createMany($schedules);
                        }
                    }
                }
                Review::approved()->delete();
                foreach (array_reverse($manifest['reviews']) as $entry) {
                    $review = new Review;
                    $review->forceFill([...$entry['data'], 'status' => ReviewStatus::Approved, 'approved_at' => now(), 'approved_by' => null])->save();
                }
                DB::table('website_imports')->insert([
                    'key' => self::MARKER, 'model' => DoctorProfile::class,
                    'record_id' => DoctorProfile::where('key', 'primary')->value('id'), 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($written);
            throw $exception;
        }

        return ['applied' => true, 'backup' => $backup];
    }

    /** @return array<string, array{source: string, file: string, alt_text: string, width: int, height: int}> */
    private function assets(array $manifest): array
    {
        $assets = [];
        foreach ($manifest['media'] as $asset) {
            if (! preg_match('/^demo-reference-[0-9]+\.webp$/D', $asset['file'])) {
                throw new RuntimeException('Invalid reference asset filename.');
            }
            $source = database_path('seeders/assets/'.$asset['file']);
            if (! is_file($source) || ! hash_equals($asset['sha256'], hash_file('sha256', $source))) {
                throw new RuntimeException('A reference photo is missing or changed. Deploy all reference assets before running this command.');
            }
            $size = getimagesize($source);
            if ($size === false || $size['mime'] !== 'image/webp') {
                throw new RuntimeException('Invalid reference image.');
            }
            $assets[$asset['key']] = ['source' => $source, 'file' => $asset['file'], 'alt_text' => $asset['alt_text'], 'width' => $size[0], 'height' => $size[1]];
        }

        return $assets;
    }

    private function mediaIds(array $entry, array $media): array
    {
        $ids = [];
        foreach ($entry['media'] ?? [] as $column => $key) {
            $ids[$column] = $key === null ? null : $media[$key];
        }

        return $ids;
    }

    private function backup(): string
    {
        $tables = [];
        foreach (config('admin.modules') as $module) {
            $table = (new $module['model'])->getTable();
            $tables[$table] = DB::table($table)->get()->all();
        }
        foreach ([DoctorProfile::class, SiteSetting::class, MediaAsset::class, ChamberSchedule::class] as $class) {
            $table = (new $class)->getTable();
            $tables[$table] = DB::table($table)->get()->all();
        }
        $tables['reviews'] = Review::approved()->get()->map(fn (Review $review): array => $review->getAttributes())->all();
        $path = 'demo-mirror-backups/'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
        if (! Storage::disk('local')->put($path, json_encode(['target_url' => config('app.url'), 'created_at' => now()->toIso8601String(), 'tables' => $tables], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('The private content backup could not be written. No content was changed.');
        }

        return $path;
    }
}
