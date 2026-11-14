<?php

namespace Tests\Feature;

use App\Models\ChamberSchedule;
use App\Models\DoctorProfile;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\User;
use App\Services\WebsiteImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['website.manifest' => database_path('seeders/data/website.json')]);
        Storage::fake('public');
    }

    public function test_saved_reference_imports_every_module_and_normalized_media_and_all_pages_render(): void
    {
        $this->artisan('website:import')->expectsOutputToContain('Created 40 content records')->assertSuccessful();
        $manifest = json_decode(file_get_contents(config('website.manifest')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['modules'] as $module => $entries) {
            $class = config('admin.modules')[$module]['model'];
            $this->assertSame(count($entries), $class::published()->count(), $module);
        }
        $this->assertSame(3, ChamberSchedule::count());
        $this->assertSame(1, Review::approved()->count());
        $this->assertSame(0, User::count());
        $this->assertGreaterThan(10, MediaAsset::count());
        foreach (MediaAsset::all() as $media) {
            Storage::disk('public')->assertExists($media->path);
            $this->assertSame('image/webp', $media->mime_type);
            $this->assertNull($media->uploaded_by);
        }
        $this->get(Slider::first()->image->url())->assertOk()->assertHeader('Content-Type', 'image/webp');
        foreach (['/', '/about', '/services', '/faqs', '/gallery', '/videos'] as $path) {
            $this->get($path)->assertOk()->assertSee('Dr. Runa Akhter Dola');
        }
        $this->get('/faqs')->assertSee(Faq::first()->question)->assertSee(Faq::first()->answer);
    }

    public function test_repeat_import_preserves_edits_cleared_settings_and_deleted_or_archived_records(): void
    {
        app(WebsiteImporter::class)->run();
        $count = MediaAsset::count();
        $mappings = DB::table('website_imports')->count();
        DoctorProfile::first()->update(['name' => 'Admin edited doctor', 'summary' => null, 'portrait_image_id' => null]);
        SiteSetting::first()->update(['phone' => null, 'social_links' => []]);
        $services = Service::ordered()->get();
        $services[0]->update(['name' => 'Edited service', 'is_active' => false]);
        $services[1]->delete();
        $services[2]->forceDelete();
        $this->artisan('website:import')->expectsOutputToContain('Created 0 content records')->assertSuccessful();
        $this->assertSame($count, MediaAsset::count());
        $this->assertSame($mappings, DB::table('website_imports')->count());
        $this->assertSame('Admin edited doctor', DoctorProfile::first()->name);
        $this->assertNull(DoctorProfile::first()->summary);
        $this->assertNull(DoctorProfile::first()->portrait_image_id);
        $this->assertNull(SiteSetting::first()->phone);
        $this->assertSame([], SiteSetting::first()->social_links);
        $this->assertDatabaseHas('services', ['id' => $services[0]->id, 'name' => 'Edited service', 'is_active' => false]);
        $this->assertSoftDeleted($services[1]);
        $this->assertDatabaseMissing('services', ['id' => $services[2]->id]);
        $this->assertSame(2, Service::withTrashed()->count());
    }

    public function test_initial_import_keeps_custom_data_and_only_fills_empty_settings(): void
    {
        $service = Service::factory()->create(['name' => 'Custom service']);
        $service->delete();
        DoctorProfile::factory()->create(['key' => 'primary', 'name' => 'Custom doctor', 'summary' => null]);
        SiteSetting::factory()->create(['key' => 'primary', 'phone' => '01700000000', 'social_links' => ['facebook' => null, 'linkedin' => 'https://linkedin.com/in/custom']]);
        $this->artisan('website:import')->expectsOutputToContain('Kept existing custom content in: service')->assertSuccessful();
        $this->assertSame(1, Service::withTrashed()->count());
        $this->assertSoftDeleted($service);
        $this->assertSame('Custom doctor', DoctorProfile::first()->name);
        $this->assertNotEmpty(DoctorProfile::first()->summary);
        $this->assertSame('01700000000', SiteSetting::first()->phone);
        $this->assertNotEmpty(SiteSetting::first()->social_links['facebook']);
        $this->assertSame('https://linkedin.com/in/custom', SiteSetting::first()->social_links['linkedin']);
    }

    public function test_missing_source_assets_fail_before_creating_records(): void
    {
        config(['website.seed_assets' => storage_path('missing-reference-source')]);
        try {
            app(WebsiteImporter::class)->run();
            $this->fail('Import should reject missing source assets.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('required local import image', $exception->getMessage());
        }
        $this->assertDatabaseCount('website_imports', 0);
        $this->assertDatabaseCount('doctor_profiles', 0);
        $this->assertDatabaseCount('media_assets', 0);
    }
}
