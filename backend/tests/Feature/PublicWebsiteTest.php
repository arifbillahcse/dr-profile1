<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\Chamber;
use App\Models\ChamberSchedule;
use App\Models\DoctorProfile;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_public_pages_render_with_an_empty_database_without_creating_records(): void
    {
        foreach (['/', '/about', '/services', '/faqs', '/gallery', '/videos'] as $path) {
            $this->get($path)->assertOk()->assertSee('id="main-content"', false)->assertSee(route('public.home'));
        }
        $this->assertDatabaseCount('doctor_profiles', 0);
        $this->assertDatabaseCount('site_settings', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public static function modules(): array
    {
        return [
            ['slider', '/', 'title'], ['featured', '/', 'label'], ['service', '/services', 'name'],
            ['education', '/', 'qualification'], ['award', '/', 'title'], ['gallery', '/gallery', 'title'],
            ['faq', '/faqs', 'question'], ['youtube', '/videos', 'title'], ['experience', '/', 'institution'],
            ['chamber', '/', 'name'], ['process', '/', 'title'], ['specialist', '/services', 'title'],
        ];
    }

    #[DataProvider('modules')]
    public function test_public_content_respects_visibility_archive_and_display_order(string $module, string $path, string $field): void
    {
        $class = config('admin.modules')[$module]['model'];
        $image = in_array($module, ['slider', 'gallery']) ? MediaAsset::factory()->create() : null;
        $attributes = $image ? ['image_id' => $image->id] : [];
        $later = $class::factory()->published()->create([...$attributes, $field => 'Public later marker', 'sort_order' => 40]);
        $earlier = $class::factory()->published()->create([...$attributes, $field => 'Public earlier marker', 'sort_order' => 10]);
        $class::factory()->create([...$attributes, $field => 'Hidden private marker', 'is_active' => false]);
        $archived = $class::factory()->published()->create([...$attributes, $field => 'Archived private marker']);
        $archived->delete();
        $this->get($path)->assertOk()->assertSeeInOrder(['Public earlier marker', 'Public later marker'])
            ->assertDontSee('Hidden private marker')->assertDontSee('Archived private marker');
        $earlier->update(['is_active' => false]);
        $later->delete();
        $this->get($path)->assertOk()->assertDontSee('Public earlier marker')->assertDontSee('Public later marker');
    }

    public function test_admin_saves_change_public_pages_immediately_and_escape_html(): void
    {
        $media = MediaAsset::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), [
            'profile' => ['name' => 'Updated doctor', 'specialty' => 'Updated specialty', 'trusted_patients' => '20k', 'summary' => 'Updated summary', 'biography' => '<script>alert(1)</script>', 'credentials_text' => "MBBS\nFCPS", 'portrait_image_id' => $media->id],
            'site' => ['site_title' => 'Updated practice', 'seo_title' => 'Updated SEO', 'seo_description' => 'Updated description', 'phone' => '+880 1700-000000', 'email' => 'updated@example.test', 'address' => 'Updated location', 'logo_image_id' => $media->id, 'favicon_image_id' => $media->id, 'social_image_id' => $media->id, 'social_links' => ['facebook' => 'https://facebook.com/updated']],
        ])->assertSessionHasNoErrors();
        $this->get('/about')->assertOk()->assertSee('Updated doctor')->assertSee('Updated specialty')->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('<title>About | Updated SEO</title>', false)
            ->assertSee('Updated description')->assertSee('updated@example.test')->assertSee('Updated location')->assertSee($media->url())
            ->assertSee('tel:+8801700000000', false)->assertSee('https://facebook.com/updated');
        SiteSetting::first()->update(['whatsapp_number' => '+8801712345678']);
        $this->get('/')->assertSee('https://wa.me/8801712345678', false)->assertSee('20k');
        SiteSetting::first()->update(['whatsapp_number' => null]);
        $this->get('/')->assertDontSee('https://wa.me/', false);
        $this->post(route('admin.content.store', 'service'), ['name' => 'Fresh public service', 'description' => 'Fresh description', 'is_active' => 1, 'sort_order' => 0])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();
        $this->get('/services')->assertSee('Fresh public service')->assertSee('Fresh description');
        $this->patch(route('admin.content.visibility', ['service', $service->id]), ['is_active' => 0])->assertRedirect();
        $this->get('/services')->assertDontSee('Fresh public service');
        $this->put(route('admin.settings.update'), ['profile' => ['name' => 'Changed again'], 'site' => ['site_title' => 'New title', 'phone' => null, 'email' => null, 'logo_image_id' => null, 'favicon_image_id' => null, 'social_image_id' => null, 'social_links' => []]])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Changed again')->assertDontSee('tel:+8801700000000', false)->assertDontSee('mailto:updated@example.test', false);
    }

    public function test_only_approved_and_unarchived_reviews_are_public(): void
    {
        Review::factory()->approved()->create(['message' => 'Approved public review']);
        Review::factory()->create(['message' => 'Pending private review']);
        Review::factory()->state(['status' => ReviewStatus::Rejected, 'approved_at' => now()])->create(['message' => 'Rejected private review']);
        Review::factory()->state(['status' => ReviewStatus::Approved, 'approved_at' => null])->create(['message' => 'Incomplete private review']);
        Review::factory()->approved()->create(['message' => 'Archived private review'])->delete();
        $this->get('/')->assertSee('Approved public review')->assertDontSee('Pending private review')->assertDontSee('Rejected private review')
            ->assertDontSee('Incomplete private review')->assertDontSee('Archived private review')->assertDontSee('localStorage', false);
    }

    public function test_chamber_hours_filter_inactive_schedules_and_hidden_chambers(): void
    {
        $chamber = Chamber::factory()->published()->create(['address' => 'Public chamber address', 'phone' => '+8801712345678', 'timezone' => 'Asia/Dhaka']);
        ChamberSchedule::factory()->for($chamber)->create(['day_of_week' => 1, 'opens_at' => '16:30:00', 'closes_at' => '20:30:00']);
        ChamberSchedule::factory()->for($chamber)->create(['day_of_week' => 2, 'opens_at' => '09:15:00', 'closes_at' => '10:15:00', 'is_active' => false]);
        $this->get('/')->assertSee('Public chamber address')->assertSee('Monday: 16:30')->assertSee('20:30')->assertSee('Asia/Dhaka')->assertDontSee('09:15');
        $chamber->update(['is_active' => false]);
        $this->get('/')->assertDontSee('Public chamber address')->assertDontSee('Monday: 16:30');
    }

    public function test_video_archive_and_legacy_links_stay_local_and_invalid_embeds_are_excluded(): void
    {
        Video::factory()->published()->count(5)->sequence(fn ($sequence) => ['title' => 'Archive video '.$sequence->index, 'sort_order' => $sequence->index])->create();
        Video::factory()->published()->create(['title' => 'Invalid private embed', 'youtube_id' => 'invalid']);
        DoctorProfile::factory()->create(['key' => 'primary', 'introduction_youtube_id' => 'invalid']);
        $this->get('/')->assertOk()->assertSee(route('public.videos'))->assertDontSee('Archive video 4')->assertDontSee('/embed/invalid', false)->assertDontSee('www.drrunaakhterdola.com/video-page', false);
        $this->get('/videos')->assertSee('Archive video 4')->assertDontSee('Invalid private embed');
        foreach (['/index.html' => '/', '/about-us.html' => '/about', '/service-page.html' => '/services', '/faq-page.html' => '/faqs', '/gallery-page.html' => '/gallery', '/video-page' => '/videos'] as $old => $new) {
            $this->get($old)->assertStatus(301)->assertRedirect($new);
        }
    }
}
