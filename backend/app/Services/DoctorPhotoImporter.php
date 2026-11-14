<?php

namespace App\Services;

use App\Models\DoctorProfile;
use App\Models\GalleryImage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Slider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DoctorPhotoImporter
{
    public const MARKER = 'shathi-facebook-photos-v1';

    public function __construct(private MediaLibrary $library) {}

    public function run(): bool
    {
        if (DB::table('website_imports')->where('key', self::MARKER)->exists()) {
            return false;
        }
        $profile = DoctorProfile::where('key', 'primary')->first();
        if (! $profile || ! DB::table('website_imports')->where('key', 'farzana-shathi-v1:profile:primary')->exists()) {
            throw new RuntimeException('Run php artisan website:doctor before importing the doctor photos.');
        }
        $photos = json_decode(file_get_contents(database_path('seeders/data/doctor-photos.json')), true, flags: JSON_THROW_ON_ERROR)['photos'];
        foreach ($photos as $photo) {
            if (! is_file(config('website.seed_assets').'/'.$photo['file'])) {
                throw new RuntimeException('A required doctor photo is missing.');
            }
        }
        $created = [];
        try {
            DB::transaction(function () use ($photos, $profile, &$created): void {
                $images = [];
                foreach ($photos as $index => $photo) {
                    $path = config('website.seed_assets').'/'.$photo['file'];
                    $media = $this->library->store(new UploadedFile($path, $photo['file'], mime_content_type($path), test: true), $photo['title']);
                    $created[] = $media;
                    $images[$photo['key']] = $media->id;
                    GalleryImage::create(['title' => $photo['title'], 'caption' => $photo['title'], 'image_id' => $media->id, 'sort_order' => $index + 1, 'is_active' => true]);
                }
                $profile->update(['portrait_image_id' => $images['portrait'], 'first_image_id' => $images['consultation'], 'second_image_id' => $images['laser-treatment'], 'tall_image_id' => $images['portrait']]);
                SiteSetting::where('key', 'primary')->update(['social_image_id' => $images['portrait']]);
                Slider::query()->delete();
                Slider::create(['title' => $profile->name, 'caption' => 'Clinical & Aesthetic Dermatology', 'image_id' => $images['portrait'], 'sort_order' => 10, 'is_active' => true]);
                Service::query()->delete();
                $services = [
                    ['Skin & Allergy', 'skin-allergy', 'Consultation for skin conditions, allergies, eczema, psoriasis and fungal concerns.', 'consultation', 'fas fa-hand-holding-medical'],
                    ['Laser & Skin Resurfacing', 'laser-skin-care', 'Consultation on laser-based skin treatments and resurfacing, following an individual skin assessment.', 'laser-treatment', 'fas fa-bolt'],
                    ['Aesthetic Dermatology', 'aesthetic-dermatology', 'Personalized discussion of skin rejuvenation, pigmentation care and aesthetic treatment options.', 'aesthetic-care', 'fas fa-spa'],
                    ['Acne & Acne Scars', 'acne-scar-care', 'Clinical assessment and a treatment plan for acne and acne scarring.', 'sparkle-profile', 'fas fa-leaf'],
                    ['Hair & Scalp', 'hair-scalp-care', 'Assessment of hair and scalp concerns with an individual care plan.', 'hair-care', 'fas fa-user-md'],
                    ['Venereology & Leprosy', 'venereology-leprosy', 'Private specialist consultation for sexual diseases and leprosy.', 'portrait', 'fas fa-stethoscope'],
                ];
                foreach ($services as $index => [$name, $slug, $description, $photo, $icon]) {
                    $baseSlug = $slug;
                    $suffix = 2;
                    while (Service::withTrashed()->where('slug', $slug)->exists()) {
                        $slug = $baseSlug.'-'.$suffix++;
                    }
                    Service::create(compact('name', 'slug', 'description', 'icon') + ['image_id' => $images[$photo], 'sort_order' => ($index + 1) * 10, 'is_active' => true]);
                }
                DB::table('website_imports')->insert(['key' => self::MARKER, 'model' => DoctorProfile::class, 'record_id' => $profile->id, 'created_at' => now(), 'updated_at' => now()]);
            });
        } catch (Throwable $exception) {
            foreach ($created as $media) {
                Storage::disk('public')->delete($media->path);
            }
            throw $exception;
        }

        return true;
    }
}
