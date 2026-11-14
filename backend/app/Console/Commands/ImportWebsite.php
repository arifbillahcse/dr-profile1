<?php

namespace App\Console\Commands;

use App\Services\WebsiteImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('website:import')]
#[Description('Import the saved public reference once, preserving administrator edits and deletions')]
class ImportWebsite extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WebsiteImporter $importer): int
    {
        $lock = Cache::lock('website-reference-import', 300);
        if (! $lock->get()) {
            $this->error('Another website import is running. Try again after it finishes.');

            return self::FAILURE;
        }
        try {
            $report = $importer->run();
            $this->info('Website import complete. Created '.$report['created'].' content records; existing imported records were kept.');
            if ($report['skipped_modules'] !== []) {
                $this->warn('Kept existing custom content in: '.implode(', ', $report['skipped_modules']));
            }

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
