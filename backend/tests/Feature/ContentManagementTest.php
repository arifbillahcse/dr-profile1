<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public static function moduleCases(): array
    {
        return [
            'slider' => ['slider', 'title', ['title' => 'Hero image', 'caption' => 'Introduction'], true],
            'featured' => ['featured', 'label', ['label' => 'Successful cases', 'value' => '2000', 'suffix' => '+', 'icon' => 'bi bi-heart-pulse'], false],
            'service' => ['service', 'name', ['name' => 'Fetal medicine', 'slug' => '', 'description' => 'Specialist consultation', 'icon' => 'bi bi-heart-pulse'], false],
            'education' => ['education', 'qualification', ['qualification' => 'FCPS', 'institution' => 'Medical college', 'year' => 2023, 'description' => 'Qualification details'], false],
            'award' => ['award', 'title', ['title' => 'Research award', 'organization' => 'Medical association', 'year' => 2019, 'description' => 'Award details'], false],
            'gallery' => ['gallery', 'title', ['title' => 'Practice gallery', 'caption' => 'Our practice'], true],
            'faq' => ['faq', 'question', ['question' => 'অ্যাপয়েন্টমেন্ট কীভাবে করব?', 'answer' => 'চেম্বারে ফোন করে সময় নিশ্চিত করুন।'], false],
            'youtube' => ['youtube', 'title', ['title' => 'Health video', 'youtube_id' => 'https://youtu.be/n7cZSkZiRbI', 'description' => 'Health information'], false],
            'experience' => ['experience', 'institution', ['institution' => 'Medical college', 'position' => 'Assistant Professor', 'started_on' => '2020-01-01', 'ended_on' => '2022-12-31', 'is_current' => 0, 'description' => 'Clinical work'], false],
            'chamber' => ['chamber', 'name', ['name' => 'Consultation chamber', 'address' => 'Dhaka', 'phone' => '+8801712345678', 'timezone' => 'Asia/Dhaka', 'additional_phones_text' => '01700000000
01800000000'], false],
            'process' => ['process', 'title', ['title' => 'Book a consultation', 'description' => 'Call the chamber', 'icon' => 'bi bi-calendar'], false],
            'specialist' => ['specialist', 'title', ['title' => 'Specialized consultation', 'description' => 'Patient care', 'icon' => 'bi bi-check-circle'], false],
        ];
    }

    #[DataProvider('moduleCases')]
    public function test_administrators_can_manage_each_content_module(string $module, string $titleField, array $data, bool $needsImage): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $media = $needsImage ? MediaAsset::factory()->create() : null;
        if ($media) {
            $data['image_id'] = $media->id;
        }
        $data += ['sort_order' => 20, 'is_active' => 0];
        $class = config('admin.modules')[$module]['model'];
        $this->get(route('admin.content.index', $module))->assertOk();
        $this->get(route('admin.content.create', $module))->assertOk();
        $this->post(route('admin.content.store', $module), $data)->assertSessionHasNoErrors();
        $record = $class::firstOrFail();
        $this->assertSame($data[$titleField], $record->$titleField);
        $this->assertFalse($record->is_active);
        $this->get(route('admin.content.edit', [$module, $record->id]))->assertOk();
        $this->patch(route('admin.content.visibility', [$module, $record->id]), ['is_active' => 1])->assertRedirect(route('admin.content.index', $module));
        $this->assertTrue($record->fresh()->is_active);
        $data[$titleField] .= ' updated';
        $data['sort_order'] = 5;
        $this->put(route('admin.content.update', [$module, $record->id]), $data)->assertSessionHasNoErrors();
        $this->assertSame($data[$titleField], $record->fresh()->$titleField);
        $this->assertSame(5, $record->fresh()->sort_order);
        $this->delete(route('admin.content.force-destroy', [$module, $record->id]))->assertNotFound();
        $this->delete(route('admin.content.destroy', [$module, $record->id]))->assertRedirect(route('admin.content.index', $module));
        $this->assertSoftDeleted($record);
        $this->get(route('admin.content.edit', [$module, $record->id]))->assertNotFound();
        $this->post(route('admin.content.restore', [$module, $record->id]))->assertRedirect(route('admin.content.index', $module));
        $this->assertNotSoftDeleted($record);
        $this->assertFalse($record->fresh()->is_active);
        $this->delete(route('admin.content.destroy', [$module, $record->id]));
        $this->delete(route('admin.content.force-destroy', [$module, $record->id]))->assertRedirect();
        $this->assertDatabaseMissing($record->getTable(), ['id' => $record->id]);
        if ($media) {
            $this->assertModelExists($media);
        }
    }

    public function test_guests_and_non_admin_users_cannot_mutate_content(): void
    {
        $service = Service::factory()->create();
        $urls = [
            ['post', route('admin.content.store', 'service')],
            ['put', route('admin.content.update', ['service', $service->id])],
            ['patch', route('admin.content.visibility', ['service', $service->id])],
            ['delete', route('admin.content.destroy', ['service', $service->id])],
            ['post', route('admin.content.restore', ['service', $service->id])],
            ['delete', route('admin.content.force-destroy', ['service', $service->id])],
        ];
        foreach ($urls as [$method, $url]) {
            $this->$method($url)->assertRedirect(route('admin.login'));
        }
        $this->actingAs(User::factory()->create());
        foreach ($urls as [$method, $url]) {
            $this->$method($url)->assertForbidden();
        }
        $this->assertNotSoftDeleted($service);
    }

    public function test_content_routes_cannot_target_settings_reviews_users_or_unknown_models(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['setting', 'review', 'users', 'media', 'unknown'] as $module) {
            $this->get('/admin/content/'.$module)->assertNotFound();
            $this->post('/admin/content/'.$module, [])->assertNotFound();
        }
    }

    public function test_unvalidated_fields_cannot_change_ids_or_archive_status(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = ['name' => 'Service', 'slug' => 'service', 'description' => 'Description', 'sort_order' => 0, 'is_active' => 0, 'id' => 999, 'deleted_at' => now()->toDateTimeString(), 'is_admin' => true];
        $this->post(route('admin.content.store', 'service'), $data)->assertSessionHasNoErrors();
        $record = Service::firstOrFail();
        $this->assertNotSame(999, $record->id);
        $this->assertNull($record->deleted_at);
        $this->assertFalse($record->is_active);
        $this->assertFalse($record->isFillable('is_admin'));
    }

    public function test_service_slugs_are_generated_and_remain_unique_including_archived_content(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = ['name' => 'Fetal Medicine', 'description' => 'Consultation', 'sort_order' => 10, 'is_active' => 0];
        $this->post(route('admin.content.store', 'service'), $data)->assertSessionHasNoErrors();
        $service = Service::firstOrFail();
        $this->assertSame('fetal-medicine', $service->slug);
        $this->put(route('admin.content.update', ['service', $service->id]), $data + ['slug' => 'fetal-medicine'])->assertSessionHasNoErrors();
        $service->delete();
        $this->post(route('admin.content.store', 'service'), $data)->assertSessionHasErrors('slug');
    }

    public function test_search_visibility_order_and_pagination_are_combined_correctly(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $visible = Service::factory()->published()->create(['name' => 'Target visible', 'sort_order' => 30]);
        $first = Service::factory()->published()->create(['name' => 'Target first', 'sort_order' => 10]);
        Service::factory()->create(['name' => 'Target hidden', 'sort_order' => 0]);
        $archived = Service::factory()->published()->create(['name' => 'Target archived']);
        $archived->delete();
        $this->get(route('admin.content.index', ['module' => 'service', 'search' => 'Target', 'status' => 'visible']))
            ->assertOk()->assertViewHas('records', fn ($records): bool => $records->pluck('id')->all() === [$first->id, $visible->id]);
        $this->get(route('admin.content.index', ['module' => 'service', 'status' => 'archived']))
            ->assertOk()->assertViewHas('records', fn ($records): bool => $records->pluck('id')->all() === [$archived->id]);
        Service::factory()->count(16)->create();
        $this->get(route('admin.content.index', 'service'))->assertOk()
            ->assertViewHas('records', fn ($records): bool => $records->count() === 15 && $records->lastPage() === 2);
    }

    public function test_invalid_images_video_urls_and_field_values_do_not_save_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $private = MediaAsset::factory()->create(['disk' => 'local']);
        $this->post(route('admin.content.store', 'gallery'), ['title' => 'Image', 'image_id' => $private->id, 'sort_order' => 0])->assertSessionHasErrors('image_id');
        $this->post(route('admin.content.store', 'youtube'), ['title' => 'Video', 'youtube_id' => 'https://youtube.com.evil.example/watch?v=n7cZSkZiRbI', 'sort_order' => 0])->assertSessionHasErrors('youtube_id');
        $this->post(route('admin.content.store', 'education'), ['qualification' => 'MBBS', 'year' => 100, 'sort_order' => -1])->assertSessionHasErrors(['year', 'sort_order']);
        $this->assertDatabaseCount('gallery_images', 0);
        $this->assertDatabaseCount('videos', 0);
        $this->assertDatabaseCount('educations', 0);
    }

    public function test_current_experience_clears_an_old_end_date_and_invalid_ranges_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $experience = Experience::factory()->create(['started_on' => '2020-01-01', 'ended_on' => '2021-01-01', 'is_current' => false]);
        $data = ['institution' => $experience->institution, 'position' => $experience->position, 'started_on' => '2020-01-01', 'is_current' => 1, 'sort_order' => 0];
        $this->put(route('admin.content.update', ['experience', $experience->id]), $data)->assertSessionHasNoErrors();
        $this->assertNull($experience->fresh()->ended_on);
        $this->assertTrue($experience->fresh()->is_current);
        $data['is_current'] = 0;
        $data['ended_on'] = '2019-01-01';
        $this->put(route('admin.content.update', ['experience', $experience->id]), $data)->assertSessionHasErrors('ended_on');
    }

    public function test_html_in_content_is_escaped_in_lists_and_editors(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $service = Service::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->get(route('admin.content.index', 'service'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('admin.content.edit', ['service', $service->id]))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
