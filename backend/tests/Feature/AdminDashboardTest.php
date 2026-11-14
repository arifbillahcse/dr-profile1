<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Service;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_totals_are_taken_from_the_database_and_exclude_deleted_content(): void
    {
        $admin = User::factory()->admin()->create();
        Slider::factory()->count(2)->create();
        Service::factory()->count(3)->create();
        Service::factory()->create()->delete();
        Review::factory()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('cards', function ($cards): bool {
                $byKey = $cards->keyBy('key');

                return $cards->count() === 12 && $byKey['slider']['count'] === 2 && $byKey['service']['count'] === 3 && $byKey['review']['count'] === 1 && $byKey['award']['count'] === 0;
            });
    }

    public function test_sidebar_sections_link_to_their_current_management_screens(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (array_keys(config('admin.modules')) as $module) {
            if ($module === 'review') {
                $this->get(route('admin.modules.show', $module))->assertRedirect(route('admin.reviews.index'));

                continue;
            }
            if ($module === 'setting') {
                $this->get(route('admin.modules.show', $module))->assertRedirect(route('admin.settings.edit'));

                continue;
            }
            if (array_key_exists($module, config('content_editor.modules'))) {
                $this->get(route('admin.modules.show', $module))->assertRedirect(route('admin.content.index', $module));

                continue;
            }
            $this->get(route('admin.modules.show', $module))->assertOk()->assertSee('Editing for this section is not available yet.');
        }
        $this->get('/admin/modules/unknown')->assertNotFound();
    }

    public function test_account_names_are_escaped_in_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['name' => '<script>alert(1)</script>']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
