<?php

namespace App\Console\Commands;

use App\Models\Chamber;
use App\Models\DoctorProfile;
use App\Models\SiteSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

#[Signature('website:practice-details')]
#[Description('Apply the client-approved qualifications and Medinova/Sparkle chamber details from November 2026 once')]
class UpdatePracticeDetails extends Command
{
    public function handle(): int
    {
        $manifest = json_decode(file_get_contents(database_path('seeders/data/farzana-shathi.json')), true, flags: JSON_THROW_ON_ERROR);
        $profile = DoctorProfile::where('key', 'primary')->where('name', $manifest['profile']['data']['name'])->first();
        if (! $profile) {
            $this->error('Import Dr. Farzana Shathi’s website first; no other doctor profile was changed.');

            return self::FAILURE;
        }

        $applied = DB::transaction(function () use ($manifest, $profile): bool {
            DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $key = 'farzana-shathi:practice-details:2026-11-24';
            if (DB::table('website_imports')->where('key', $key)->exists()) {
                return false;
            }

            $profile->update(Arr::only($manifest['profile']['data'], ['designation', 'specialty', 'credentials', 'summary', 'biography']));
            $site = SiteSetting::firstOrCreate(['key' => 'primary']);
            $site->fill(Arr::only($manifest['site']['data'], ['phone', 'additional_phones', 'address', 'seo_description', 'seo_keywords']));
            $site->social_links = [...($site->social_links ?? []), 'facebook' => $manifest['site']['data']['social_links']['facebook']];
            $site->save();

            foreach (['education', 'experience', 'process', 'faq', 'chamber'] as $module) {
                $class = config('admin.modules')[$module]['model'];
                foreach ($manifest['modules'][$module] as $index => $entry) {
                    $mappingKey = $manifest['identity'].':content:'.$module.':'.$index;
                    $mapping = DB::table('website_imports')->where('key', $mappingKey)->first();
                    if ($mapping && $mapping->model !== $class) {
                        continue;
                    }
                    $record = $mapping ? $class::withTrashed()->find($mapping->record_id) : null;
                    if (! $mapping && $module === 'chamber') {
                        $record = Chamber::withTrashed()->firstOrNew(['name' => $entry['data']['name']]);
                    }
                    if (! $record || $record->trashed()) {
                        continue;
                    }
                    $record->fill(Arr::except($entry['data'], ['schedules']));
                    if (! $record->exists) {
                        $record->sort_order = ($index + 1) * 10;
                        $record->is_active = true;
                    }
                    $record->save();
                    if ($module === 'chamber') {
                        $record->schedules()->delete();
                        $record->schedules()->createMany($entry['data']['schedules']);
                    }
                    if (! $mapping) {
                        DB::table('website_imports')->insert(['key' => $mappingKey, 'model' => $class, 'record_id' => $record->id, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }

            DB::table('website_imports')->insert(['key' => $key, 'model' => DoctorProfile::class, 'record_id' => $profile->id, 'created_at' => now(), 'updated_at' => now()]);

            return true;
        });
        $this->info($applied ? 'Updated qualifications, memberships and both Medinova and Sparkle chamber details.' : 'Practice details were already applied; later administrator edits were kept.');

        return self::SUCCESS;
    }
}
