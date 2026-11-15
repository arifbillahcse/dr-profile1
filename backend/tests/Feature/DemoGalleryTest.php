<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_six_cards_expand_to_three_rows_without_copying_files_or_changing_originals(): void
    {
        $this->createGallery();
        $originals = GalleryImage::ordered()->get()->toArray();
        $this->artisan('website:demo-gallery')->expectsOutputToContain('Created 6 additional gallery cards')->assertSuccessful();
        $this->assertDatabaseCount('gallery_images', 15);
        $this->assertDatabaseCount('media_assets', 9);
        $response = $this->get('/gallery')->assertOk();
        $this->assertSame(12, substr_count($response->getContent(), 'class="gallery-curated-card"'));
        $this->assertSame($originals, GalleryImage::whereIn('id', array_column($originals, 'id'))->ordered()->get()->toArray());
        $this->assertSame(6, DB::table('website_imports')->where('key', 'like', 'demo:gallery:slot:%')->count());
    }

    public function test_repeat_setup_preserves_replacements_and_does_not_restore_hidden_or_deleted_cards(): void
    {
        $this->createGallery();
        $this->artisan('website:demo-gallery')->assertSuccessful();
        $copies = GalleryImage::whereIn('id', DB::table('website_imports')->where('key', 'like', 'demo:gallery:slot:%')->pluck('record_id'))->ordered()->get();
        $replacement = MediaAsset::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.content.update', ['gallery', $copies[0]->id]), [
            'title' => 'My replacement photograph', 'image_id' => $replacement->id, 'sort_order' => 100, 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $copies[1]->update(['is_active' => false]);
        $copies[2]->delete();
        $copies[3]->forceDelete();
        $this->artisan('website:demo-gallery')->expectsOutputToContain('Created 0 additional gallery cards')->assertSuccessful();
        $this->assertSame(14, GalleryImage::withTrashed()->count());
        $this->assertSame($replacement->id, $copies[0]->fresh()->image_id);
        $this->assertSame('My replacement photograph', $copies[0]->fresh()->title);
        $this->assertFalse($copies[1]->fresh()->is_active);
        $this->assertTrue($copies[2]->fresh()->trashed());
    }

    public function test_full_gallery_is_kept_and_empty_gallery_cannot_create_broken_cards(): void
    {
        $this->artisan('website:demo-gallery')->assertFailed();
        $this->assertDatabaseCount('gallery_images', 0);
        $this->assertDatabaseCount('website_imports', 0);
        $this->createGallery();
        $source = GalleryImage::ordered()->firstOrFail();
        GalleryImage::factory()->published()->count(6)->create(['image_id' => $source->image_id]);
        $this->artisan('website:demo-gallery')->expectsOutputToContain('Created 0 additional gallery cards')->assertSuccessful();
        $this->assertDatabaseCount('gallery_images', 15);
    }

    private function createGallery(): void
    {
        $portrait = MediaAsset::factory()->create(['width' => 600, 'height' => 800]);
        DoctorProfile::factory()->create(['key' => 'primary', 'portrait_image_id' => $portrait->id]);
        GalleryImage::factory()->published()->create(['image_id' => $portrait->id, 'sort_order' => 10]);
        for ($index = 0; $index < 8; $index++) {
            $image = MediaAsset::factory()->create(['width' => $index < 2 ? 800 : 600, 'height' => $index < 2 ? 600 : 800]);
            GalleryImage::factory()->published()->create(['image_id' => $image->id, 'sort_order' => ($index + 2) * 10]);
        }
    }
}
