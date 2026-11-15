<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\InlineImageUploads;
use App\Services\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class InlineImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public static function imageModules(): array
    {
        return [
            ['slider', ['title' => 'New slide']],
            ['service', ['name' => 'Skin consultation', 'description' => 'Consultation details']],
            ['education', ['qualification' => 'MBBS']],
            ['award', ['title' => 'Award']],
            ['gallery', ['title' => 'Practice photograph']],
            ['youtube', ['title' => 'Video', 'youtube_id' => 'n7cZSkZiRbI']],
            ['experience', ['institution' => 'Medical college', 'position' => 'Assistant Professor']],
        ];
    }

    #[DataProvider('imageModules')]
    public function test_image_can_be_uploaded_in_each_content_form(string $module, array $data): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $field = InlineImageUploads::contentFields($module)[0];
        $this->get(route('admin.content.create', $module))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="uploads['.$field.']"', false)
            ->assertSee('Upload new image')->assertDontSee('and reload this page');
        $this->post(route('admin.content.store', $module), $data + [
            'sort_order' => 10, 'is_active' => 1,
            'uploads' => [$field => UploadedFile::fake()->image('direct-upload.jpg', 100, 100)],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $model = config('admin.modules.'.$module.'.model');
        $record = $model::firstOrFail();
        $media = MediaAsset::findOrFail($record->$field);
        $this->assertSame($admin->id, $media->uploaded_by);
        $this->assertSame('image/webp', $media->mime_type);
        Storage::disk('public')->assertExists($media->path);
        $this->get(route('admin.content.edit', [$module, $record->id]))->assertOk()->assertSee('Current image');
    }

    public function test_replacing_one_image_keeps_other_sections_and_the_original_file(): void
    {
        $old = app(MediaLibrary::class)->store(UploadedFile::fake()->image('shared.jpg'), 'Shared image');
        $gallery = GalleryImage::factory()->create(['image_id' => $old->id]);
        $service = Service::factory()->create(['image_id' => $old->id]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.content.update', ['gallery', $gallery->id]), [
            '_method' => 'PUT', 'title' => 'Replaced photograph', 'sort_order' => 10, 'image_id' => $old->id,
            'uploads' => ['image_id' => UploadedFile::fake()->image('replacement.png')],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotSame($old->id, $gallery->fresh()->image_id);
        $this->assertSame($old->id, $service->fresh()->image_id);
        Storage::disk('public')->assertExists($old->path);
        $new = $gallery->fresh()->image;
        $this->post(route('admin.content.update', ['gallery', $gallery->id]), [
            '_method' => 'PUT', 'title' => 'Text-only edit', 'sort_order' => 10, 'image_id' => $new->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($new->id, $gallery->fresh()->image_id);
        $this->assertSame(2, MediaAsset::count());
    }

    public function test_all_settings_photos_and_branding_images_upload_in_one_save(): void
    {
        $data = ['profile' => ['name' => 'Doctor'], 'site' => ['site_title' => 'Practice']];
        foreach (InlineImageUploads::settingsFields() as $field) {
            Arr::set($data, 'uploads.'.$field, UploadedFile::fake()->image(str_replace('.', '-', $field).'.jpg'));
        }
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.settings.update'), $data + ['_method' => 'PUT'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $profile = DoctorProfile::firstOrFail();
        $site = SiteSetting::firstOrFail();
        foreach (InlineImageUploads::settingsFields() as $field) {
            [$group, $column] = explode('.', $field);
            $this->assertNotNull(($group === 'profile' ? $profile : $site)->$column);
        }
        $this->assertSame(7, MediaAsset::count());
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('name="uploads[profile][portrait_image_id]"', false);
    }

    public function test_invalid_uploads_and_unknown_fields_do_not_change_content(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([
            UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->image('large.jpg')->size(5121),
            UploadedFile::fake()->image('wide.png', 4097, 1),
            UploadedFile::fake()->create('fake.jpg', 1, 'image/jpeg'),
        ] as $file) {
            $this->post(route('admin.content.store', 'gallery'), [
                'title' => 'Invalid photograph', 'sort_order' => 10, 'uploads' => ['image_id' => $file],
            ])->assertSessionHasErrors('uploads.image_id');
        }
        $this->post(route('admin.content.store', 'gallery'), [
            'title' => 'Invalid field', 'sort_order' => 10, 'uploads' => ['logo_image_id' => UploadedFile::fake()->image('wrong.jpg')],
        ])->assertSessionHasErrors();
        $this->assertSame(0, MediaAsset::count());
        $this->assertSame(0, GalleryImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_settings_validation_failure_does_not_upload_or_change_images(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.settings.update'), [
            '_method' => 'PUT', 'profile' => ['name' => 'Doctor'], 'site' => ['site_title' => 'Practice', 'email' => 'invalid'],
            'uploads' => ['profile' => ['portrait_image_id' => UploadedFile::fake()->image('portrait.jpg')]],
        ])->assertSessionHasErrors('site.email');
        $this->assertSame(0, DoctorProfile::count());
        $this->assertSame(0, MediaAsset::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_save_failure_removes_new_uploads_and_rolls_back_media_records(): void
    {
        $request = Request::create('/', 'POST', [], [], ['uploads' => ['image_id' => UploadedFile::fake()->image('rollback.jpg')]]);
        try {
            app(InlineImageUploads::class)->save($request, [], ['image_id'], function (array $data): void {
                throw new RuntimeException('Failed content save');
            });
            $this->fail('Expected the content save to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Failed content save', $exception->getMessage());
        }
        $this->assertSame(0, MediaAsset::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_administrator_cannot_upload_through_content_forms(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.content.store', 'gallery'), [
            'title' => 'Unauthorized', 'sort_order' => 10, 'uploads' => ['image_id' => UploadedFile::fake()->image('forbidden.jpg')],
        ])->assertForbidden();
        $this->assertSame(0, MediaAsset::count());
    }

    public function test_cross_removes_an_existing_gallery_image_without_deleting_shared_media(): void
    {
        $old = app(MediaLibrary::class)->store(UploadedFile::fake()->image('shared.jpg'), null);
        $gallery = GalleryImage::factory()->create(['image_id' => $old->id]);
        $service = Service::factory()->create(['image_id' => $old->id]);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.content.update', ['gallery', $gallery->id]), [
            '_method' => 'PUT', 'title' => 'Gallery item', 'sort_order' => 10, 'image_id' => $old->id,
            'remove_images' => ['image_id' => 1],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($gallery->fresh()->image_id);
        $this->assertSame($old->id, $service->fresh()->image_id);
        $this->assertModelExists($old);
        Storage::disk('public')->assertExists($old->path);
        $this->post(route('admin.content.update', ['gallery', $gallery->id]), [
            '_method' => 'PUT', 'title' => 'Updated without an image', 'sort_order' => 10, 'image_id' => null,
        ])->assertSessionHasNoErrors();
        $this->assertNull($gallery->fresh()->image_id);
        $this->post(route('admin.content.update', ['gallery', $gallery->id]), [
            '_method' => 'PUT', 'title' => 'Gallery item', 'sort_order' => 10, 'remove_images' => ['image_id' => 1],
            'uploads' => ['image_id' => UploadedFile::fake()->image('new.jpg')],
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($gallery->fresh()->image_id);
        $this->assertNotSame($old->id, $gallery->fresh()->image_id);
    }

    public function test_settings_removal_only_clears_the_requested_image_slot(): void
    {
        $media = MediaAsset::factory()->create();
        $profile = DoctorProfile::factory()->create(['key' => 'primary', 'portrait_image_id' => $media->id]);
        $site = SiteSetting::factory()->create(['key' => 'primary', 'logo_image_id' => $media->id]);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.settings.update'), [
            '_method' => 'PUT', 'profile' => ['name' => 'Doctor', 'portrait_image_id' => $media->id],
            'site' => ['site_title' => 'Practice', 'logo_image_id' => $media->id],
            'remove_images' => ['profile' => ['portrait_image_id' => 1]],
        ])->assertSessionHasNoErrors();
        $this->assertNull($profile->fresh()->portrait_image_id);
        $this->assertSame($media->id, $site->fresh()->logo_image_id);
        $this->assertModelExists($media);
    }

    public function test_removal_does_not_bypass_other_validation_or_new_gallery_image_requirement(): void
    {
        $media = MediaAsset::factory()->create();
        $gallery = GalleryImage::factory()->create(['image_id' => $media->id]);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.content.update', ['gallery', $gallery->id]), [
            '_method' => 'PUT', 'title' => '', 'sort_order' => 10, 'remove_images' => ['image_id' => 1],
        ])->assertSessionHasErrors('title');
        $this->assertSame($media->id, $gallery->fresh()->image_id);
        $this->post(route('admin.content.store', 'gallery'), [
            'title' => 'New item', 'sort_order' => 10, 'remove_images' => ['image_id' => 1],
        ])->assertSessionHasErrors('image_id');
    }
}
