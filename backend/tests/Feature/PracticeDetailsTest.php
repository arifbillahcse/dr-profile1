<?php

namespace Tests\Feature;

use App\Models\Chamber;
use App\Models\DoctorProfile;
use App\Models\Education;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\WebsiteImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PracticeDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_existing_practice_gets_the_supplied_qualifications_and_both_chambers_without_replacing_other_content(): void
    {
        app(WebsiteImporter::class)->run();
        $sparkle = Chamber::where('name', 'like', 'Sparkle%')->firstOrFail();
        DB::table('website_imports')->where('key', 'farzana-shathi-v1:content:chamber:1')->delete();
        $sparkle->forceDelete();
        DoctorProfile::first()->update(['credentials' => ['Old credentials'], 'biography' => 'Old biography']);
        Education::where('qualification', 'DDV')->update(['institution' => null]);
        $medinova = Chamber::firstOrFail();
        $medinova->update(['name' => 'Medinova Medical Services Ltd. — Malibagh Branch']);
        $user = User::factory()->admin()->create();
        $review = Review::factory()->create()->fresh();
        $imageIds = DoctorProfile::first()->only(['portrait_image_id', 'first_image_id', 'second_image_id', 'tall_image_id']);
        SiteSetting::first()->update(['email' => 'personal@example.com', 'social_links' => ['instagram' => 'https://instagram.com/practice']]);

        $this->artisan('website:practice-details')->expectsOutputToContain('Updated qualifications')->assertSuccessful();
        $this->assertDatabaseCount('chambers', 2);
        $this->assertDatabaseHas('chambers', ['id' => $medinova->id, 'name' => 'Medinova Medical Services Ltd.', 'phone' => '01790118855']);
        $this->assertSame(['01790118866', '0258311-13'], $medinova->fresh()->additional_phones);
        $sparkle = Chamber::where('name', 'like', 'Sparkle%')->firstOrFail();
        $this->assertSame('info@sparkleaestheticbd.com', $sparkle->email);
        $this->assertSame('0963297137', $sparkle->phone);
        $this->assertSame(['01310519250'], $sparkle->additional_phones);
        $this->assertSame(4, $sparkle->schedules()->first()->day_of_week);
        $this->assertSame('15:00', substr($sparkle->schedules()->first()->opens_at, 0, 5));
        $this->assertSame('17:00', substr($sparkle->schedules()->first()->closes_at, 0, 5));
        $this->assertSame(3, $medinova->schedules()->count());
        $this->assertCount(9, DoctorProfile::first()->credentials);
        $this->assertSame($imageIds, DoctorProfile::first()->only(array_keys($imageIds)));
        $this->assertModelExists($user);
        $this->assertSame($review->toArray(), $review->fresh()->toArray());
        $this->assertSame('personal@example.com', SiteSetting::first()->email);
        $this->assertSame('https://instagram.com/practice', SiteSetting::first()->social_links['instagram']);
        $this->assertSame('https://www.facebook.com/dr.farzanashathi', SiteSetting::first()->social_links['facebook']);
        foreach (['/', '/about', '/services', '/faqs', '/gallery'] as $url) {
            $this->get($url)->assertOk()->assertSee('Medinova Medical Services Ltd.')->assertSee('Sparkle Skin, Laser &amp; Aesthetic Center', false)->assertSee('info@sparkleaestheticbd.com');
        }
        $this->get('/')->assertSee('Professional memberships')->assertSee('American College of Physicians (ACP)')->assertSee('International Society of Dermatology (ISD)')->assertSee('American Academy of Aesthetic Medicine (AAAM)')->assertSee('IEB-UK, Malaysia')->assertSee('Thursday: 15:00');
        $this->get('/about')->assertSee('Bangladesh Medical University')->assertSee('Shaheed Suhrawardy Medical College Hospital');
    }

    public function test_repeat_application_and_import_do_not_duplicate_chambers_or_overwrite_later_edits(): void
    {
        app(WebsiteImporter::class)->run();
        $this->artisan('website:practice-details')->assertSuccessful();
        DoctorProfile::first()->update(['summary' => 'Later approved summary']);
        $sparkle = Chamber::where('name', 'like', 'Sparkle%')->firstOrFail();
        $sparkle->update(['phone' => '01700000000', 'is_active' => false]);
        $sparkle->schedules()->delete();
        $this->artisan('website:practice-details')->expectsOutputToContain('already applied')->assertSuccessful();
        $this->artisan('website:import')->assertSuccessful();
        $this->assertDatabaseCount('chambers', 2);
        $this->assertSame('Later approved summary', DoctorProfile::first()->summary);
        $this->assertSame('01700000000', $sparkle->fresh()->phone);
        $this->assertFalse($sparkle->fresh()->is_active);
        $this->assertSame(0, $sparkle->schedules()->count());
        $this->get('/')->assertDontSee('info@sparkleaestheticbd.com');
    }

    public function test_aaam_membership_is_shown_in_every_requested_credential_area(): void
    {
        app(WebsiteImporter::class)->run();
        $membership = 'Member of American Academy of Aesthetic Medicine (AAAM)';
        foreach ([
            '/' => ['hero-header', 'introduction-qualifications', 'qualification-award-area', 'footer-about'],
            '/about' => ['academic-luxury-card', 'footer-about'],
        ] as $url => $sections) {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML($response->getContent());
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new \DOMXPath($document);
            foreach ($sections as $section) {
                $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " '.$section.' ")]');
                $this->assertSame(1, $nodes->length, $url.' '.$section);
                $this->assertStringContainsString($membership, $nodes->item(0)->textContent, $url.' '.$section);
            }
        }
    }

    public function test_archived_or_deleted_imported_content_is_not_recreated(): void
    {
        app(WebsiteImporter::class)->run();
        $sparkle = Chamber::where('name', 'like', 'Sparkle%')->firstOrFail();
        $sparkle->delete();
        Education::first()->forceDelete();
        $this->artisan('website:practice-details')->assertSuccessful();
        $this->assertSoftDeleted($sparkle);
        $this->assertDatabaseCount('educations', 5);
    }

    public function test_another_doctors_profile_is_never_changed(): void
    {
        DoctorProfile::factory()->create(['key' => 'primary', 'name' => 'Another doctor']);
        $this->artisan('website:practice-details')->assertFailed();
        $this->assertSame('Another doctor', DoctorProfile::first()->name);
        $this->assertDatabaseCount('chambers', 0);
    }
}
