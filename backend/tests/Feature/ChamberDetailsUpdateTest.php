<?php

namespace Tests\Feature;

use App\Models\Chamber;
use App\Models\ChamberSchedule;
use App\Models\DoctorProfile;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChamberDetailsUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_demo_deployment_publishes_both_chambers_with_correct_contacts_and_schedules(): void
    {
        $this->artisan('website:match-demo')->assertSuccessful();
        $profile = DoctorProfile::firstOrFail();
        $preserved = $profile->only(['credentials', 'portrait_image_id', 'summary']);
        $user = User::factory()->admin()->create()->fresh()->getAttributes();
        $reviewCount = Review::count();
        $facebook = SiteSetting::firstOrFail()->social_links;

        $this->artisan('website:chambers')->expectsOutputToContain('Both Medinova and Sparkle')->assertSuccessful();

        $this->assertSame(2, Chamber::published()->count());
        $medinova = Chamber::published()->where('name', 'Medinova Medical Services Ltd.')->firstOrFail();
        $sparkle = Chamber::published()->where('name', 'Sparkle Skin, Laser & Aesthetic Center')->firstOrFail();
        $this->assertSame('Gemcon Business Center, 255 New Circular Road, Malibag, Dhaka-1217', $medinova->address);
        $this->assertSame('01790118855', $medinova->phone);
        $this->assertSame(['01790118866', '0258311-13'], $medinova->additional_phones);
        $this->assertSame([1, 3, 6], $medinova->schedules->pluck('day_of_week')->all());
        foreach ($medinova->schedules as $schedule) {
            $this->assertSame('18:00', substr($schedule->opens_at, 0, 5));
            $this->assertSame('21:00', substr($schedule->closes_at, 0, 5));
        }
        $this->assertSame('145/1, Crescent Plaza (2nd Floor), Green Road, Dhaka', $sparkle->address);
        $this->assertSame('0963297137', $sparkle->phone);
        $this->assertSame(['01310519250'], $sparkle->additional_phones);
        $this->assertSame('info@sparkleaestheticbd.com', $sparkle->email);
        $this->assertSame([4], $sparkle->schedules->pluck('day_of_week')->all());
        $this->assertSame('15:00', substr($sparkle->schedules->first()->opens_at, 0, 5));
        $this->assertSame('17:00', substr($sparkle->schedules->first()->closes_at, 0, 5));
        $this->assertSame($preserved, $profile->fresh()->only(array_keys($preserved)));
        $this->assertSame($user, User::findOrFail($user['id'])->getAttributes());
        $this->assertSame($reviewCount, Review::count());
        $this->assertSame($facebook, SiteSetting::firstOrFail()->social_links);
        foreach (['/', '/about', '/services', '/faqs', '/gallery', '/videos'] as $url) {
            $this->get($url)->assertOk()->assertSee('Medinova Medical Services Ltd.')
                ->assertSee('Sparkle Skin, Laser &amp; Aesthetic Center', false)
                ->assertSee('Thursday: 15:00')->assertSee('mailto:info@sparkleaestheticbd.com', false)
                ->assertSee('tel:01310519250', false)->assertSee('0258311-13');
        }
        $this->get('/')->assertSee('Choose Your Chamber')->assertSee('Visit Your Chosen Clinic')->assertDontSee('Visit Medinova Malibagh');
        $this->get('/about')->assertSee('She consults at two clinics in Dhaka');
        $this->get('/faqs')->assertSee('তিনি ঢাকায় দুটি ক্লিনিকে রোগী দেখেন।');
    }

    public function test_redeployment_reuses_existing_chambers_and_preserves_later_admin_edits(): void
    {
        $this->artisan('website:import')->assertSuccessful();
        $this->artisan('website:match-demo')->assertSuccessful();
        $this->artisan('website:chambers')->assertSuccessful();
        $counts = [Chamber::count(), ChamberSchedule::count()];
        $sparkle = Chamber::published()->where('name', 'like', 'Sparkle%')->firstOrFail();
        $sparkle->update(['phone' => '01700000000']);
        $sparkle->schedules()->delete();
        $this->artisan('website:match-demo')->assertSuccessful();
        $this->artisan('website:chambers')->expectsOutputToContain('already applied')->assertSuccessful();
        $this->assertSame(2, Chamber::published()->count());
        $this->assertSame($counts[0], Chamber::count());
        $this->assertSame($counts[1] - 1, ChamberSchedule::count());
        $this->assertSame('01700000000', $sparkle->fresh()->phone);
        $this->assertSame(0, $sparkle->schedules()->count());
    }

    public function test_missing_or_other_doctor_is_not_modified(): void
    {
        $this->artisan('website:chambers')->assertFailed();
        DoctorProfile::factory()->create(['key' => 'primary', 'name' => 'Another doctor']);
        $this->artisan('website:chambers')->assertFailed();
        $this->assertDatabaseCount('chambers', 0);
        $this->assertSame('Another doctor', DoctorProfile::first()->name);
    }
}
