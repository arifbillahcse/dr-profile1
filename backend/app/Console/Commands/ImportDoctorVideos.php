<?php

namespace App\Console\Commands;

use App\Models\DoctorProfile;
use App\Models\Video;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('website:doctor-videos')]
#[Description('Import three videos from Dr. Farzana Shathi’s YouTube channel and hide unchanged Blender demos')]
class ImportDoctorVideos extends Command
{
    public function handle(): int
    {
        $lock = Cache::lock('website-demo-videos', 300);
        if (! $lock->get()) {
            $this->error('Video setup is already running. Try again after it finishes.');

            return self::FAILURE;
        }

        try {
            [$created, $hidden, $translated] = DB::transaction(function (): array {
                // Verified against https://www.youtube.com/channel/UCqKbSj9L-VAO2AhctwmfFOg.
                $selection = [
                    [
                        'id' => 'QUtAjd4n3Co',
                        'title' => 'ব্রণ কেন হয় ও এর চিকিৎসা',
                        'description' => 'ব্রণ হওয়ার কারণ ও চিকিৎসা নিয়ে আলোচনা করেছেন ডা. ফারজানা রহমান সাথী।',
                        'previous' => ['title' => 'Acne: Causes & Treatment', 'description' => 'Dr. Farzana Rahman Shathi discusses acne and its treatment in Bangla.'],
                    ],
                    [
                        'id' => 'rMdIkmYD-yA',
                        'title' => 'ব্রণের দাগ ও ত্বকের গর্ত',
                        'description' => 'ব্রণের দাগ ও ত্বকের গর্তের যত্ন নিয়ে ডা. ফারজানা রহমান সাথীর আলোচনা।',
                        'previous' => ['title' => 'Acne Scars & Skin Texture', 'description' => 'A Bangla discussion on acne scars and skin texture with Dr. Farzana Rahman Shathi.'],
                    ],
                    [
                        'id' => 'scWIJ8pmROg',
                        'title' => 'চুল পড়ার কারণ ও যত্ন',
                        'description' => 'চুল পড়ার কারণ ও চুলের যত্ন সম্পর্কে জানাচ্ছেন ডা. ফারজানা রহমান সাথী।',
                        'previous' => ['title' => 'Hair Fall: Causes & Care', 'description' => 'Dr. Farzana Rahman Shathi discusses hair fall and hair care in Bangla.'],
                    ],
                ];
                $created = 0;
                $translated = 0;
                foreach ($selection as $index => $item) {
                    $key = 'doctor:video:youtube:'.$item['id'];
                    $import = DB::table('website_imports')->where('key', $key)->first();
                    if ($import) {
                        $video = Video::whereKey($import->record_id)->where('youtube_id', $item['id'])->first();
                        if ($video) {
                            $changes = [];
                            foreach (['title', 'description'] as $field) {
                                if ($video->{$field} === $item['previous'][$field]) {
                                    $changes[$field] = $item[$field];
                                }
                            }
                            if ($changes !== []) {
                                $video->update($changes);
                                $translated++;
                            }
                        }

                        continue;
                    }
                    $video = Video::withTrashed()->firstOrCreate(['youtube_id' => $item['id']], [
                        'title' => $item['title'], 'description' => $item['description'],
                        'sort_order' => ($index + 1) * 10, 'is_active' => true,
                    ]);
                    DB::table('website_imports')->insert([
                        'key' => $key, 'model' => Video::class, 'record_id' => $video->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $created += $video->wasRecentlyCreated ? 1 : 0;
                }

                $demos = ['aqz-KE-bpKQ' => 'Big Buck Bunny', 'eRsGyueVLvQ' => 'Sintel', 'R6MlUcmOul8' => 'Tears of Steel'];
                $hidden = 0;
                foreach ($demos as $youtubeId => $title) {
                    $hidden += Video::where('youtube_id', $youtubeId)
                        ->where('title', 'Demo video - '.$title)
                        ->where('description', 'Sample video by Blender for layout preview. Not a video from this practice.')
                        ->where('is_active', true)->update(['is_active' => false]);
                }
                DoctorProfile::where('key', 'primary')->whereIn('introduction_youtube_id', array_keys($demos))
                    ->update(['introduction_youtube_id' => null]);

                return [$created, $hidden, $translated];
            });
            $this->info('Added '.$created.' doctor videos; hid '.$hidden.' unchanged demo videos; translated '.$translated.' existing video captions. Existing administrator edits and deletions were kept.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
