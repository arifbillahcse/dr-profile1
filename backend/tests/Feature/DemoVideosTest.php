<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoVideosTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_published_samples_appear_on_home_and_video_archive(): void
    {
        $this->artisan('website:demo-videos')->expectsOutputToContain('Created 3 demo videos')->assertSuccessful();
        $this->assertSame(3, Video::published()->count());
        foreach (['/', '/videos'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee('Demo video - Big Buck Bunny')->assertSee('Demo video - Sintel')->assertSee('Demo video - Tears of Steel')->assertSee('Not a video from this practice.');
            $this->assertSame(3, substr_count($response->getContent(), 'class="practice-video-card"'));
            $this->assertSame(3, substr_count($response->getContent(), 'youtube-nocookie.com/embed/'));
        }
    }

    public function test_repeat_setup_preserves_admin_replacements_hidden_archived_and_deleted_samples(): void
    {
        $this->artisan('website:demo-videos')->assertSuccessful();
        [$edited, $hidden, $archived] = Video::ordered()->get()->all();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.content.update', ['youtube', $edited->id]), [
            'title' => 'My own video', 'youtube_id' => 'M7lc1UVf-VE', 'sort_order' => 5, 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $hidden->update(['is_active' => false]);
        $archived->delete();
        $this->artisan('website:demo-videos')->expectsOutputToContain('Created 0 demo videos')->assertSuccessful();
        $this->assertSame(3, Video::withTrashed()->count());
        $this->assertSame('M7lc1UVf-VE', $edited->fresh()->youtube_id);
        $this->assertFalse($hidden->fresh()->is_active);
        $this->assertTrue($archived->fresh()->trashed());
        $edited->forceDelete();
        $this->artisan('website:demo-videos')->assertSuccessful();
        $this->assertSame(2, Video::withTrashed()->count());
    }

    public function test_setup_keeps_existing_custom_content_and_does_not_duplicate_matching_video(): void
    {
        $custom = Video::factory()->published()->create(['title' => 'Existing custom video', 'sort_order' => 50]);
        $existing = Video::factory()->create(['title' => 'My existing Sintel', 'youtube_id' => 'eRsGyueVLvQ', 'sort_order' => 60]);
        $this->artisan('website:demo-videos')->expectsOutputToContain('Created 2 demo videos')->assertSuccessful();
        $this->assertSame(4, Video::withTrashed()->count());
        $this->assertSame('Existing custom video', $custom->fresh()->title);
        $this->assertSame('My existing Sintel', $existing->fresh()->title);
        $this->assertFalse($existing->fresh()->is_active);
        $this->assertGreaterThan(60, Video::where('youtube_id', 'aqz-KE-bpKQ')->firstOrFail()->sort_order);
    }
}
