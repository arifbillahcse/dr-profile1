<?php

namespace App\Console\Commands;

use App\Models\DoctorProfile;
use App\Models\GalleryImage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('website:demo-gallery')]
#[Description('Fill the gallery collection to twelve editable cards once, reusing existing images')]
class CreateDemoGallery extends Command
{
    public function handle(): int
    {
        $lock = Cache::lock('website-demo-gallery', 300);
        if (! $lock->get()) {
            $this->error('Gallery setup is already running. Try again after it finishes.');

            return self::FAILURE;
        }
        try {
            $created = DB::transaction(function (): ?int {
                if (DB::table('website_imports')->where('key', 'demo:gallery:collection-v1')->exists()) {
                    return 0;
                }
                $gallery = GalleryImage::published()->ordered()->with('image')->whereHas('image')->get();
                $portraitId = DoctorProfile::where('key', 'primary')->value('portrait_image_id');
                $cover = $gallery->firstWhere('image_id', $portraitId)
                    ?? $gallery->first(fn (GalleryImage $photo): bool => $photo->image->width >= 400);
                $moments = $gallery->filter(fn (GalleryImage $photo): bool => $photo->id !== $cover?->id && $photo->image->width >= 600 && $photo->image->width > $photo->image->height)->take(2);
                $collection = $gallery->reject(fn (GalleryImage $photo): bool => $photo->id === $cover?->id || $moments->contains('id', $photo->id))->values();
                if ($collection->count() >= 12) {
                    return 0;
                }
                if ($collection->isEmpty()) {
                    return null;
                }
                $created = 0;
                $order = (int) GalleryImage::withTrashed()->max('sort_order');
                for ($slot = $collection->count() + 1; $slot <= 12; $slot++) {
                    $source = $collection[$created % $collection->count()];
                    $order = min(1000000, $order + 10);
                    $photo = GalleryImage::create([
                        'title' => $source->title, 'caption' => $source->caption, 'image_id' => $source->image_id,
                        'sort_order' => $order, 'is_active' => true,
                    ]);
                    DB::table('website_imports')->insert([
                        'key' => 'demo:gallery:slot:'.$slot, 'model' => GalleryImage::class, 'record_id' => $photo->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $created++;
                }
                DB::table('website_imports')->insert([
                    'key' => 'demo:gallery:collection-v1', 'model' => GalleryImage::class, 'record_id' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                return $created;
            });
            if ($created === null) {
                $this->error('Add at least one published gallery collection image before creating additional cards.');

                return self::FAILURE;
            }
            $this->info('Created '.$created.' additional gallery cards. Existing images, edits and deletions were kept.');

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
