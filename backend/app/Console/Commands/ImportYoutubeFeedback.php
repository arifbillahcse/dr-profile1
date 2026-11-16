<?php

namespace App\Console\Commands;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('website:youtube-feedback')]
#[Description('Publish sourced public feedback in Bangla, preserving author names and moderation decisions')]
class ImportYoutubeFeedback extends Command
{
    public function handle(): int
    {
        $lock = Cache::lock('website-youtube-feedback', 300);
        if (! $lock->get()) {
            $this->error('Public feedback setup is already running. Try again after it finishes.');

            return self::FAILURE;
        }
        try {
            [$created, $translated] = DB::transaction(function (): array {
                // Public feedback checked on 17 November 2026; source URLs are retained for administrators.
                $comments = [
                    [
                        'id' => 'UgyWORg-pofWtNQXf5N4AaABAg', 'video' => 'scWIJ8pmROg',
                        'name' => '@SharifHossain-s2u',
                        'message' => 'ম্যাডামের চিকিৎসা খুবই চমৎকার। আমি উনার থেকে ব্রণের চিকিৎসা নিচ্ছি, আশানুরূপ এখনই ভালো ফলাফল পাচ্ছি।',
                    ],
                    [
                        'id' => 'UgycyvK3SoQH0PRO8Q54AaABAg', 'video' => 'Jp-Tz-BYNG0',
                        'name' => '@shihabchowdhury2820', 'message' => 'চিকিৎসাটি খুব ভালো ছিল।',
                        'original_message' => 'It’s was great treatment',
                    ],
                    [
                        'id' => 'UgxwPeei_FRbCwP82Ht4AaABAg', 'video' => 'L9Pqi-nbUSU',
                        'name' => '@mahimsumi6509', 'message' => 'উনি খুবই ভালো একজন ডাক্তার 🥰',
                    ],
                    [
                        'key' => 'directory:feedback:doctorbangladesh:504',
                        'source_type' => 'doctor_bangladesh',
                        'source_url' => 'https://www.doctorbangladesh.com/dr-farzana-rahman-shathi/#comment-504',
                        'name' => 'Yeasif Ferdous', 'rating' => 5,
                        'message' => 'সহযোগিতাপরায়ণ চিকিৎসক, ভালো মানুষ। রোগীদের আন্তরিকভাবে চিকিৎসা করেন এবং তাঁর ব্যবহার ভালো।',
                        'original_message' => 'Cooperative doctor,good human being,treat patients very solely,good behaviour.',
                    ],
                ];
                $created = 0;
                $translated = 0;
                foreach ($comments as $comment) {
                    $key = $comment['key'] ?? 'youtube:feedback:'.$comment['id'];
                    $url = $comment['source_url'] ?? 'https://www.youtube.com/watch?v='.$comment['video'].'&lc='.$comment['id'];
                    $sourceType = $comment['source_type'] ?? 'youtube';
                    $import = DB::table('website_imports')->where('key', $key)->first();
                    if ($import) {
                        if (isset($comment['original_message'])) {
                            $translated += Review::whereKey($import->record_id)
                                ->where('source_type', $sourceType)->where('source_url', $url)
                                ->where('message', $comment['original_message'])->update(['message' => $comment['message']]);
                        }

                        continue;
                    }
                    $review = Review::withTrashed()->where('source_type', $sourceType)->where('source_url', $url)->first();
                    if (! $review) {
                        $review = new Review;
                        $review->forceFill([
                            'name' => $comment['name'], 'message' => $comment['message'], 'rating' => $comment['rating'] ?? null,
                            'source_type' => $sourceType, 'source_url' => $url,
                            'status' => ReviewStatus::Approved, 'approved_at' => now(),
                        ])->save();
                        $created++;
                    }
                    DB::table('website_imports')->insert([
                        'key' => $key, 'model' => Review::class, 'record_id' => $review->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                return [$created, $translated];
            });
            $this->info('Published '.$created.' sourced feedback entries; translated '.$translated.' existing entries. Existing moderation decisions and deletions were kept.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
