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
            $response = $this->get($path)->assertOk()->assertSee('Acne: Causes &amp; Treatment', false)
                ->assertSee('Acne Scars &amp; Skin Texture', false)->assertSee('Hair Fall: Causes &amp; Care', false)
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
}
