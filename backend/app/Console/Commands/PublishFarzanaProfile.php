<?php

namespace App\Console\Commands;

use App\Services\WebsiteImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('website:doctor')]
#[Description('Apply the verified Farzana Shathi profile once, archiving the previous doctor content')]
class PublishFarzanaProfile extends Command
{
    public function handle(WebsiteImporter $importer): int
    {
        $lock = Cache::lock('website-reference-import', 300);
        if (! $lock->get()) {
            $this->error('Another website import is running. Please try again after it finishes.');

            return self::FAILURE;
        }

        try {
            config(['website.manifest' => database_path('seeders/data/farzana-shathi.json')]);
            if (DB::table('website_imports')->where('key', 'farzana-shathi-v1:profile:primary')->exists()) {
                $this->info('Doctor profile already applied. Later administrator edits were kept.');

                return self::SUCCESS;
            }

            DB::transaction(fn () => $importer->run(replaceDoctor: true));
            $this->info('Dr. Farzana Rahman Shathi profile applied. Previous content archived; administrator accounts and passwords preserved.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
