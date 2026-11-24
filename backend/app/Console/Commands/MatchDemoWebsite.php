<?php

namespace App\Console\Commands;

use App\Services\DemoWebsiteMirror;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

#[Signature('website:match-demo')]
#[Description('Match the public website to the approved live demo snapshot, with a private content backup and unchanged administrator accounts')]
class MatchDemoWebsite extends Command
{
    public function handle(DemoWebsiteMirror $mirror): int
    {
        $lock = Cache::lock('website-reference-import', 300);
        if (! $lock->get()) {
            $this->error('Another website import is running. Try again when it finishes.');

            return self::FAILURE;
        }
        try {
            $result = $mirror->run();
            $this->info($result['applied'] ? 'Demo matched: 6 services, 9 gallery photos, 3 videos and 4 published reviews.' : 'The demo snapshot was already applied; later administrator edits were kept.');
            if ($result['backup']) {
                $this->line('Previous public content backup: storage/app/private/'.$result['backup']);
            }

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
