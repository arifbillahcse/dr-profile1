<?php

namespace Tests\Feature;

use App\Models\Chamber;
use App\Models\DoctorProfile;
use App\Models\FeaturedStatistic;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FarzanaProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_switch_archives_previous_doctor_and_preserves_administrator_access(): void
    {
        $admin = User::factory()->admin()->create();
        $password = $admin->password;
        config(['website.manifest' => database_path('seeders/data/website.json')]);
        $this->artisan('website:import')->assertSuccessful();
        $oldService = Service::first();
        $oldReview = Review::first();
        $this->artisan('website:doctor')->assertSuccessful();

        $this->assertSoftDeleted($oldService);
        $this->assertSoftDeleted($oldReview);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertTrue($admin->fresh()->is_admin);
        $this->assertSame(1, User::count());
        $this->assertSame(0, Review::approved()->count());
        $this->assertSame(0, Video::published()->count());
        $this->assertSame(0, FeaturedStatistic::published()->count());
        $this->assertSame('Dr. Farzana Rahman Shathi', DoctorProfile::first()->name);
        $this->assertNull(DoctorProfile::first()->introduction_youtube_id);
        $this->assertNull(DoctorProfile::first()->trusted_patients);
        $this->assertSame(['facebook' => 'https://www.facebook.com/dr.shathi'], SiteSetting::first()->social_links);
        $this->assertNull(SiteSetting::first()->email);
        $this->assertNull(SiteSetting::first()->whatsapp_number);
        $this->assertNull(SiteSetting::first()->logo_image_id);
        $chamber = Chamber::first();
        $this->assertStringContainsString('Room 701, Lift 6', $chamber->address);
        $this->assertEqualsCanonicalizing([1, 3, 6], $chamber->schedules->pluck('day_of_week')->all());
        foreach ($chamber->schedules as $schedule) {
            $this->assertStringStartsWith('18:00', $schedule->opens_at);
            $this->assertStringStartsWith('21:00', $schedule->closes_at);
        }

        foreach (['/', '/about', '/services', '/faqs', '/gallery', '/videos'] as $path) {
            $this->get($path)->assertOk()->assertSee('Dr. Farzana Rahman Shathi')
                ->assertDontSee('Runa')->assertDontSee('Pregnancy')->assertDontSee('n7cZSkZiRbI');
        }
        $this->get(DoctorProfile::first()->portrait->url())->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_repeat_update_and_import_keep_later_edits_and_archives(): void
    {
        $this->artisan('website:doctor')->assertSuccessful();
        $count = MediaAsset::count();
        $mappings = DB::table('website_imports')->count();
        DoctorProfile::first()->update(['summary' => 'Edited by administrator']);
        $service = Service::first();
        $service->delete();
        $this->artisan('website:doctor')->expectsOutputToContain('already applied')->assertSuccessful();
        $this->artisan('website:import')->assertSuccessful();
        $this->assertSame('Edited by administrator', DoctorProfile::first()->summary);
        $this->assertSoftDeleted($service);
        $this->assertSame($count, MediaAsset::count());
        $this->assertSame($mappings, DB::table('website_imports')->count());
    }

    public function test_fresh_install_imports_new_doctor_without_old_records(): void
    {
        $this->artisan('website:import')->assertSuccessful();
        $this->assertSame('Dr. Farzana Rahman Shathi', DoctorProfile::first()->name);
        $this->assertStringStartsWith('Dr. Farzana Rahman Shathi', DoctorProfile::first()->biography);
        $this->assertSame(3, Service::published()->count());
        $this->assertSame(2, MediaAsset::count());
        $this->assertSame(0, Review::count());
        $this->artisan('website:doctor')->expectsOutputToContain('already applied')->assertSuccessful();
    }

    public function test_biography_name_correction_preserves_other_text_and_later_edits(): void
    {
        $suffix = "\n\nExisting administrator biography.";
        $profile = DoctorProfile::factory()->create(['key' => 'primary', 'name' => 'Dr. Farzana Rahman Shathi', 'biography' => 'ডাঃ ফারজানা রহমান সাথী'.$suffix]);
        $migration = require database_path('migrations/2026_11_15_181325_use_english_doctor_name_in_biography.php');
        $migration->up();
        $this->assertSame('Dr. Farzana Rahman Shathi'.$suffix, $profile->fresh()->biography);
        $this->get('/about')->assertOk()->assertSee('<strong class="fw-semibold">Dr. Farzana Rahman Shathi</strong>', false)->assertSee('Existing administrator biography.');
        $profile->update(['biography' => 'Later edited biography.']);
        $migration->up();
        $migration->down();
        $this->assertSame('Later edited biography.', $profile->fresh()->biography);
    }

    public function test_missing_photo_does_not_archive_existing_content(): void
    {
        $service = Service::factory()->create();
        config(['website.seed_assets' => storage_path('missing-doctor-assets')]);
        try {
            $this->artisan('website:doctor');
            $this->fail('Missing media must reject the update.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('required local import image', $exception->getMessage());
        }
        $this->assertNotSoftDeleted($service);
        $this->assertDatabaseCount('website_imports', 0);
        $this->assertDatabaseCount('doctor_profiles', 0);
    }
}
