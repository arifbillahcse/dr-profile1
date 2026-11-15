<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('website:demo-videos')]
#[Description('Add three editable sample videos once, preserving existing content and later administrator edits')]
class CreateDemoVideos extends Command
{
    public function handle(): int
    {
        $lock = Cache::lock('website-demo-videos', 300);
        if (! $lock->get()) {
            $this->error('Demo video setup is already running. Try again after it finishes.');

            return self::FAILURE;
        }
        try {
            $created = DB::transaction(function (): int {
                $created = 0;
                $samples = ['aqz-KE-bpKQ' => 'Big Buck Bunny', 'eRsGyueVLvQ' => 'Sintel', 'R6MlUcmOul8' => 'Tears of Steel'];
                $order = (int) Video::withTrashed()->max('sort_order');
                foreach ($samples as $youtubeId => $title) {
                    $key = 'demo:video:'.$youtubeId;
                    if (DB::table('website_imports')->where('key', $key)->exists()) {
                        continue;
                    }
                    $order = min(1000000, $order + 10);
                    $video = Video::withTrashed()->firstOrCreate(['youtube_id' => $youtubeId], [
                        'title' => 'Demo video - '.$title,
                        'description' => 'Sample video by Blender for layout preview. Not a video from this practice.',
                        'sort_order' => $order,
                        'is_active' => true,
                    ]);
                    DB::table('website_imports')->insert([
                        'key' => $key, 'model' => Video::class, 'record_id' => $video->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $created += $video->wasRecentlyCreated ? 1 : 0;
                }

                return $created;
            });
            $this->info('Created '.$created.' demo videos. Existing videos, edits and deletions were kept.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
