<?php

namespace App\Console\Commands;

use App\Services\DoctorPhotoImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('website:photos')]
#[Description('Apply the supplied doctor photographs and service collection once')]
class ImportDoctorPhotos extends Command
{
    public function handle(DoctorPhotoImporter $importer): int
    {
        $lock = Cache::lock('website-reference-import', 300);
        if (! $lock->get()) {
            $this->error('Another website import is running. Please try again later.');

            return self::FAILURE;
        }
        try {
            $this->info($importer->run() ? 'Doctor photographs and services updated. Accounts, chamber details and reviews preserved.' : 'Photographs already applied. Later edits were kept.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
