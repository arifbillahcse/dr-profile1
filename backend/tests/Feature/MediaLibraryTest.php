<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\User;
use App\Services\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function upload(string $name = 'portrait.png'): MediaAsset
    {
        $response = $this->post(route('admin.media.store'), ['image' => UploadedFile::fake()->image($name, 180, 240), 'alt_text' => 'Doctor portrait']);
        $response->assertSessionHasNoErrors();

        return MediaAsset::latest('id')->firstOrFail();
    }

    public function test_uploads_require_an_administrator(): void
    {
        $this->get(route('admin.media.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.media.store'), ['image' => UploadedFile::fake()->image('test.png')])->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())->get(route('admin.media.index'))->assertForbidden();
        $this->post(route('admin.media.store'), ['image' => UploadedFile::fake()->image('test.png')])->assertForbidden();
        $this->assertSame(0, MediaAsset::count());
    }

    public function test_valid_upload_is_normalized_and_has_metadata_and_a_public_image_url(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $media = $this->upload();
        Storage::disk('public')->assertExists($media->path);
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame(180, $media->width);
        $this->assertSame(240, $media->height);
        $this->assertSame($admin->id, $media->uploaded_by);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertNotSame('portrait.png', basename($media->path));
        $this->assertSame($media->size_bytes, Storage::disk('public')->size($media->path));
        $this->get($media->url())->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('admin.media.edit', $media))->assertOk()->assertSee('Doctor portrait');
    }

    public function test_script_svg_oversized_and_excessive_dimension_uploads_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([
            UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('large.jpg')->size(5121),
            UploadedFile::fake()->image('wide.png', 4097, 1),
        ] as $image) {
            $this->post(route('admin.media.store'), ['image' => $image])->assertSessionHasErrors('image');
        }
        $this->assertSame(0, MediaAsset::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_replacement_preserves_references_changes_version_and_removes_the_old_file(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = $this->upload();
        $oldPath = $media->path;
        $oldUrl = $media->url();
        $profile = DoctorProfile::factory()->create(['portrait_image_id' => $media->id]);
        $this->put(route('admin.media.update', $media), ['image' => UploadedFile::fake()->image('new.jpg', 320, 200), 'alt_text' => 'Updated portrait'])->assertSessionHasNoErrors();
        $updated = $media->fresh();
        $this->assertSame($media->id, $profile->fresh()->portrait_image_id);
        $this->assertNotSame($oldUrl, $updated->url());
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updated->path);
        $this->assertSame(320, $updated->width);
        $this->assertSame('Updated portrait', $updated->alt_text);
        $this->assertSame(1, MediaAsset::count());
    }

    public function test_invalid_replacement_keeps_existing_file_and_metadata(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = $this->upload();
        $this->put(route('admin.media.update', $media), ['image' => UploadedFile::fake()->createWithContent('bad.jpg', 'not an image'), 'alt_text' => 'Should not save'])->assertSessionHasErrors('image');
        $this->assertSame('Doctor portrait', $media->fresh()->alt_text);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_alt_text_can_be_edited_without_replacing_the_file(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = $this->upload();
        $this->put(route('admin.media.update', $media), ['alt_text' => 'Accessible image description'])->assertSessionHasNoErrors();
        $this->assertSame($media->path, $media->fresh()->path);
        $this->assertSame('Accessible image description', $media->fresh()->alt_text);
    }

    public function test_referenced_images_cannot_be_deleted_even_when_content_is_archived(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = $this->upload();
        $service = Service::factory()->create(['image_id' => $media->id]);
        $service->delete();
        $this->delete(route('admin.media.destroy', $media))->assertSessionHasErrors('media');
        $this->assertModelExists($media);
        Storage::disk('public')->assertExists($media->path);
        $this->assertSame(['Services' => 1], app(MediaLibrary::class)->references($media));
    }

    public function test_unused_images_are_removed_from_storage_and_database(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = $this->upload();
        $this->delete(route('admin.media.destroy', $media))->assertRedirect(route('admin.media.index'));
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
        $this->get($media->url())->assertNotFound();
    }

    public function test_private_or_unmanaged_paths_cannot_be_served_or_modified(): void
    {
        $media = MediaAsset::factory()->create(['disk' => 'local', 'path' => '../.env']);
        $this->get($media->url())->assertNotFound();
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.media.update', $media), ['alt_text' => 'Changed'])->assertSessionHasErrors('media');
        $this->delete(route('admin.media.destroy', $media))->assertSessionHasErrors('media');
        $this->assertModelExists($media);
    }

    public function test_search_and_pagination_work_without_exposing_other_disks(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->upload('doctor-portrait.png');
        MediaAsset::factory()->create(['disk' => 'local', 'original_name' => 'private-doctor.png']);
        $this->get(route('admin.media.index', ['search' => 'doctor']))->assertOk()->assertSee('doctor-portrait.png')->assertDontSee('private-doctor.png');
        $this->get(route('admin.media.index', ['search' => 'not-found']))->assertOk()->assertSee('No matching images');
    }

    public function test_library_paginates_public_assets(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        MediaAsset::factory()->count(13)->create();
        $this->get(route('admin.media.index'))->assertOk()->assertViewHas('mediaItems', fn ($items): bool => $items->count() === 12 && $items->lastPage() === 2);
        $this->get(route('admin.media.index', ['page' => 2]))->assertOk()->assertViewHas('mediaItems', fn ($items): bool => $items->count() === 1);
    }
}
