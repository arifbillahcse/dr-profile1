<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function settingsData(): array
    {
        return [
            'profile' => ['name' => 'ডা. রুনা আক্তার দোলা', 'designation' => 'Assistant Professor', 'credentials_text' => "MBBS\nFCPS\n\nFeto-Maternal Medicine", 'summary' => 'বিশেষজ্ঞ চিকিৎসক', 'biography' => "Biography paragraph one.\nParagraph two.", 'introduction_youtube_id' => 'https://youtu.be/n7cZSkZiRbI'],
            'site' => ['site_title' => 'Doctor website', 'phone' => '+8801712345678', 'additional_phones_text' => "01700000000\n01800000000", 'email' => 'practice@example.test', 'address' => 'ঢাকা', 'whatsapp_number' => '+8801712345678', 'social_links' => ['facebook' => 'https://www.facebook.com/doctor', 'youtube' => 'https://www.youtube.com/@doctor'], 'seo_title' => 'Doctor in Dhaka', 'seo_description' => 'Practice information', 'seo_keywords' => 'doctor, Dhaka'],
        ];
    }

    public function test_only_administrators_can_view_or_update_settings(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.settings.update'), $this->settingsData())->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())->get(route('admin.settings.edit'))->assertForbidden();
        $this->put(route('admin.settings.update'), $this->settingsData())->assertForbidden();
        $this->assertSame(0, SiteSetting::count());
    }

    public function test_settings_save_profile_contacts_social_links_and_seo_and_survive_reloading(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.settings.edit'))->assertOk();
        $this->put(route('admin.settings.update'), $this->settingsData())->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();
        $profile = DoctorProfile::where('key', 'primary')->firstOrFail();
        $site = SiteSetting::where('key', 'primary')->firstOrFail();
        $this->assertSame(['MBBS', 'FCPS', 'Feto-Maternal Medicine'], $profile->credentials);
        $this->assertSame('n7cZSkZiRbI', $profile->introduction_youtube_id);
        $this->assertSame(['01700000000', '01800000000'], $site->additional_phones);
        $this->assertSame('https://www.facebook.com/doctor', $site->social_links['facebook']);
        $this->assertSame('Doctor in Dhaka', $site->seo_title);
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('ডা. রুনা আক্তার দোলা');
        $this->get(route('admin.dashboard'))->assertSee('Doctor website');
        $this->seed(DatabaseSeeder::class);
        $this->assertSame('ডা. রুনা আক্তার দোলা', $profile->fresh()->name);
        $this->assertSame(1, DoctorProfile::count());
        $this->assertSame(1, SiteSetting::count());
    }

    public function test_invalid_video_social_link_email_or_image_id_prevents_all_changes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $originalName = DoctorProfile::first()->name;
        $data = $this->settingsData();
        $data['profile']['introduction_youtube_id'] = 'https://youtube.com.evil.example/watch?v=n7cZSkZiRbI';
        $data['profile']['portrait_image_id'] = 99999;
        $data['site']['email'] = 'invalid-email';
        $data['site']['social_links']['facebook'] = 'javascript:alert(1)';
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), $data)
            ->assertSessionHasErrors(['profile.introduction_youtube_id', 'profile.portrait_image_id', 'site.email', 'site.social_links.facebook']);
        $this->assertSame($originalName, DoctorProfile::first()->name);
        $this->assertDatabaseMissing('site_settings', ['site_title' => 'Doctor website']);
    }

    public function test_image_selection_can_be_replaced_and_cleared_without_deleting_shared_assets(): void
    {
        $old = MediaAsset::factory()->create();
        $new = MediaAsset::factory()->create();
        $this->seed(DatabaseSeeder::class);
        $site = SiteSetting::first();
        $site->update(['logo_image_id' => $old->id]);
        $data = $this->settingsData();
        $data['site']['logo_image_id'] = $new->id;
        $data['profile']['portrait_image_id'] = $new->id;
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), $data)->assertSessionHasNoErrors();
        $this->assertSame($new->id, $site->fresh()->logo_image_id);
        $this->assertModelExists($old);
        $data['site']['logo_image_id'] = null;
        $this->put(route('admin.settings.update'), $data)->assertSessionHasNoErrors();
        $this->assertNull($site->fresh()->logo_image_id);
        $this->assertSame($new->id, DoctorProfile::first()->portrait_image_id);
        $this->assertModelExists($new);
    }

    public function test_private_assets_and_unapproved_settings_keys_are_rejected(): void
    {
        $private = MediaAsset::factory()->create(['disk' => 'local']);
        $data = $this->settingsData();
        $data['site']['logo_image_id'] = $private->id;
        $data['profile']['key'] = 'another-profile';
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), $data)
            ->assertSessionHasErrors(['site.logo_image_id', 'profile']);
        $this->assertSame(0, DoctorProfile::count());
    }

    public function test_profile_html_is_displayed_as_text_in_the_editor(): void
    {
        $this->seed(DatabaseSeeder::class);
        DoctorProfile::first()->update(['biography' => '<script>alert(1)</script>']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.settings.edit'))
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
