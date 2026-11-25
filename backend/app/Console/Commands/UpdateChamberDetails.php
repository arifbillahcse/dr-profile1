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

#[Signature('website:chambers')]
#[Description('Apply approved Medinova and Sparkle consultation details once after the demo import')]
class UpdateChamberDetails extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $manifest = json_decode(file_get_contents(database_path('seeders/data/farzana-shathi.json')), true, flags: JSON_THROW_ON_ERROR);
        $reference = json_decode(file_get_contents(database_path('seeders/data/demo-reference.json')), true, flags: JSON_THROW_ON_ERROR);
        $applied = DB::transaction(function () use ($manifest, $reference): ?bool {
            $profile = DoctorProfile::where('key', 'primary')->where('name', $manifest['profile']['data']['name'])->lockForUpdate()->first();
            if (! $profile) {
                return null;
            }
            $key = 'farzana-shathi:two-chambers:2026-11-25';
            if (DB::table('website_imports')->where('key', $key)->exists()) {
                return false;
            }
            foreach ($manifest['modules']['chamber'] as $index => $entry) {
                $prefix = $index === 0 ? 'Medinova%' : 'Sparkle%';
                $chamber = Chamber::where('name', 'like', $prefix)->orderByDesc('is_active')->orderBy('id')->first() ?? new Chamber;
                $chamber->fill([...Arr::except($entry['data'], ['schedules']), 'sort_order' => ($index + 1) * 10, 'is_active' => true])->save();
                $chamber->schedules()->delete();
                $chamber->schedules()->createMany($entry['data']['schedules']);
                Chamber::where('name', 'like', $prefix)->whereKeyNot($chamber->id)->update(['is_active' => false]);
            }
            SiteSetting::where('key', 'primary')->first()?->update(Arr::only($manifest['site']['data'], ['phone', 'additional_phones', 'address', 'seo_description']));
            $oldParagraph = last(explode("\n\n", $reference['profile']['data']['biography']));
            $newParagraph = last(explode("\n\n", $manifest['profile']['data']['biography']));
            if (str_ends_with($profile->biography ?? '', $oldParagraph)) {
                $profile->update(['biography' => substr($profile->biography, 0, -strlen($oldParagraph)).$newParagraph]);
            }
            foreach (['process' => [0, 1], 'faq' => [1, 2]] as $module => $indices) {
                $class = config('admin.modules')[$module]['model'];
                $field = $module === 'faq' ? 'question' : 'title';
                foreach ($indices as $index) {
                    $original = $reference['modules'][$module][$index]['data'];
                    $replacement = $manifest['modules'][$module][$index]['data'];
                    $class::published()->where($field, $original[$field])->update($replacement);
                }
            }
            DB::table('website_imports')->insert(['key' => $key, 'model' => DoctorProfile::class, 'record_id' => $profile->id, 'created_at' => now(), 'updated_at' => now()]);

            return true;
        });
        if ($applied === null) {
            $this->error('Import Dr. Farzana Rahman Shathi first. No other doctor was changed.');

            return self::FAILURE;
        }
        $this->info($applied ? 'Both Medinova and Sparkle chambers, schedules and contacts updated.' : 'Chamber details already applied; later administrator edits kept.');

        return self::SUCCESS;
    }
}
