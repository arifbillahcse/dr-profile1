<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorVideosTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_videos_replace_demo_display_on_home_archive_and_introduction(): void
    {
        $this->artisan('website:demo-videos')->assertSuccessful();
        $profile = DoctorProfile::factory()->create(['key' => 'primary', 'introduction_youtube_id' => 'aqz-KE-bpKQ']);
        $this->artisan('website:doctor-videos')->expectsOutputToContain('Added 3 doctor videos; hid 3 unchanged demo videos')->assertSuccessful();
        $this->assertSame(6, Video::count());
        $this->assertSame(3, Video::published()->count());
        $this->assertNull($profile->fresh()->introduction_youtube_id);

        foreach (['/', '/videos'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee('ব্রণ কেন হয় ও এর চিকিৎসা')
                ->assertSee('ব্রণের দাগ ও ত্বকের গর্ত')->assertSee('চুল পড়ার কারণ ও যত্ন')
                ->assertSee('ব্রণ হওয়ার কারণ ও চিকিৎসা নিয়ে আলোচনা করেছেন ডা. ফারজানা রহমান সাথী।')
                ->assertSee('ব্রণের দাগ ও ত্বকের গর্তের যত্ন নিয়ে ডা. ফারজানা রহমান সাথীর আলোচনা।')
                ->assertSee('চুল পড়ার কারণ ও চুলের যত্ন সম্পর্কে জানাচ্ছেন ডা. ফারজানা রহমান সাথী।')
                ->assertDontSee('Big Buck Bunny')->assertDontSee('Sintel')->assertDontSee('Tears of Steel');
            foreach (['QUtAjd4n3Co', 'rMdIkmYD-yA', 'scWIJ8pmROg'] as $id) {
                $response->assertSee('youtube-nocookie.com/embed/'.$id, false)->assertSee('youtube.com/watch?v='.$id, false);
            }
        }
        $this->get('/')->assertViewHas('introductionVideoId', 'QUtAjd4n3Co');
    }

    public function test_repeated_import_keeps_edits_hidden_archived_and_deleted_videos(): void
    {
        $this->artisan('website:doctor-videos')->assertSuccessful();
        [$edited, $hidden, $archived] = Video::ordered()->get()->all();
        $edited->update(['title' => 'Administrator title', 'youtube_id' => 'M7lc1UVf-VE']);
        $hidden->update(['is_active' => false]);
        $archived->delete();
        $this->artisan('website:doctor-videos')->expectsOutputToContain('Added 0 doctor videos; hid 0 unchanged demo videos')->assertSuccessful();
        $this->assertSame(3, Video::withTrashed()->count());
        $this->assertSame('Administrator title', $edited->fresh()->title);
        $this->assertSame('M7lc1UVf-VE', $edited->fresh()->youtube_id);
        $this->assertFalse($hidden->fresh()->is_active);
        $this->assertTrue($archived->fresh()->trashed());
        $edited->forceDelete();
        $this->artisan('website:doctor-videos')->assertSuccessful();
        $this->assertSame(2, Video::withTrashed()->count());
    }

    public function test_existing_video_choices_and_custom_introduction_are_preserved(): void
    {
        $this->artisan('website:demo-videos')->assertSuccessful();
        $editedDemo = Video::where('youtube_id', 'eRsGyueVLvQ')->firstOrFail();
        $editedDemo->update(['title' => 'Administrator choice']);
        $existing = Video::factory()->create(['youtube_id' => 'QUtAjd4n3Co', 'title' => 'Existing acne video', 'sort_order' => 80]);
        $profile = DoctorProfile::factory()->create(['key' => 'primary', 'introduction_youtube_id' => 'scWIJ8pmROg']);
        $this->artisan('website:doctor-videos')->expectsOutputToContain('Added 2 doctor videos; hid 2 unchanged demo videos')->assertSuccessful();
        $this->assertTrue($editedDemo->fresh()->is_active);
        $this->assertSame('Existing acne video', $existing->fresh()->title);
        $this->assertFalse($existing->fresh()->is_active);
        $this->assertSame(80, $existing->fresh()->sort_order);
        $this->assertSame('scWIJ8pmROg', $profile->fresh()->introduction_youtube_id);
        $this->artisan('website:demo-videos')->expectsOutputToContain('Created 0 demo videos')->assertSuccessful();
        $this->assertSame(3, Video::published()->count());
    }

    public function test_existing_english_captions_are_translated_without_overwriting_administrator_changes(): void
    {
        $this->artisan('website:doctor-videos')->assertSuccessful();
        $acne = Video::where('youtube_id', 'QUtAjd4n3Co')->firstOrFail();
        $scars = Video::where('youtube_id', 'rMdIkmYD-yA')->firstOrFail();
        $hair = Video::where('youtube_id', 'scWIJ8pmROg')->firstOrFail();
        $acne->update(['title' => 'Acne: Causes & Treatment', 'description' => 'Dr. Farzana Rahman Shathi discusses acne and its treatment in Bangla.', 'sort_order' => 80, 'is_active' => false]);
        $scars->update(['title' => 'Administrator title', 'description' => 'A Bangla discussion on acne scars and skin texture with Dr. Farzana Rahman Shathi.']);
        $hair->update(['title' => 'Hair Fall: Causes & Care', 'description' => 'Administrator description']);

        $this->artisan('website:doctor-videos')->expectsOutputToContain('translated 3 existing video captions')->assertSuccessful();
        $this->assertSame('ব্রণ কেন হয় ও এর চিকিৎসা', $acne->fresh()->title);
        $this->assertSame('ব্রণ হওয়ার কারণ ও চিকিৎসা নিয়ে আলোচনা করেছেন ডা. ফারজানা রহমান সাথী।', $acne->fresh()->description);
        $this->assertFalse($acne->fresh()->is_active);
        $this->assertSame(80, $acne->fresh()->sort_order);
        $this->assertSame('Administrator title', $scars->fresh()->title);
        $this->assertSame('ব্রণের দাগ ও ত্বকের গর্তের যত্ন নিয়ে ডা. ফারজানা রহমান সাথীর আলোচনা।', $scars->fresh()->description);
        $this->assertSame('চুল পড়ার কারণ ও যত্ন', $hair->fresh()->title);
        $this->assertSame('Administrator description', $hair->fresh()->description);
        $this->artisan('website:doctor-videos')->expectsOutputToContain('translated 0 existing video captions')->assertSuccessful();
        $this->assertSame(3, Video::count());
    }
}
