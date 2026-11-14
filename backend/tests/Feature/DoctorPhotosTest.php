<?php

namespace Tests\Feature;

use App\Models\Chamber;
use App\Models\DoctorProfile;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\DoctorPhotoImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_photos_update_profile_services_and_gallery_without_changing_accounts_chambers_or_reviews(): void
    {
        $this->artisan('website:doctor')->assertSuccessful();
        $admin = User::factory()->admin()->create();
        $password = $admin->password;
        $review = Review::factory()->create();
        $chamber = Chamber::first()->toArray();
        $oldService = Service::first();
        $this->artisan('website:photos')->assertSuccessful();
        $this->assertSame(6, Service::published()->count());
        $this->assertSame(9, GalleryImage::published()->count());
        $this->assertSame(9, MediaAsset::count());
        $this->assertSoftDeleted($oldService);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertModelExists($review);
        $this->assertSame($chamber, Chamber::first()->toArray());
        $this->assertSame('shathi-portrait.jpg', DoctorProfile::first()->portrait->original_name);
        foreach (Service::published()->with('image')->get() as $service) {
            Storage::disk('public')->assertExists($service->image->path);
            $this->get($service->image->url())->assertOk();
        }
        foreach (['/', '/about', '/services', '/gallery'] as $url) {
            $this->get($url)->assertOk()->assertSee('Dr. Farzana Rahman Shathi')->assertDontSee('fbcdn.net');
        }
        $this->get('/')->assertSee('A closer look at')->assertSee('Laser &amp; Skin Resurfacing', false);
    }

    public function test_repeat_command_keeps_later_edits_and_does_not_duplicate_images(): void
    {
        $this->artisan('website:doctor')->assertSuccessful();
        $this->artisan('website:photos')->assertSuccessful();
        $service = Service::first();
        $service->update(['name' => 'Administrator edited service']);
        GalleryImage::first()->delete();
        DoctorProfile::first()->update(['portrait_image_id' => null]);
        $this->artisan('website:photos')->expectsOutputToContain('already applied')->assertSuccessful();
        $this->assertSame(9, MediaAsset::count());
        $this->assertSame(8, GalleryImage::count());
        $this->assertSame('Administrator edited service', $service->fresh()->name);
        $this->assertNull(DoctorProfile::first()->portrait_image_id);
    }

    public function test_missing_photo_leaves_existing_services_and_profile_untouched(): void
    {
        $this->artisan('website:doctor')->assertSuccessful();
        $portrait = DoctorProfile::first()->portrait_image_id;
        config(['website.seed_assets' => storage_path('missing-doctor-photos')]);
        try {
            app(DoctorPhotoImporter::class)->run();
            $this->fail('Missing photos must reject the update.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('photo is missing', $exception->getMessage());
        }
        $this->assertSame($portrait, DoctorProfile::first()->portrait_image_id);
        $this->assertSame(3, Service::published()->count());
        $this->assertSame(2, MediaAsset::count());
    }
}
