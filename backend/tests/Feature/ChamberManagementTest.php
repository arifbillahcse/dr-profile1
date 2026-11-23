<?php

namespace Tests\Feature;

use App\Models\Chamber;
use App\Models\ChamberSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChamberManagementTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $schedules = []): array
    {
        return [
            'name' => 'Dhaka chamber', 'address' => 'ঢাকা', 'phone' => '+8801712345678',
            'additional_phones_text' => "01700000000\n01800000000", 'timezone' => 'Asia/Dhaka', 'email' => 'clinic@example.com',
            'sort_order' => 10, 'is_active' => 0, 'schedules_present' => 1, 'schedules' => $schedules,
        ];
    }

    private function slot(int $day, string $opens = '17:00', string $closes = '21:00', bool $active = true): array
    {
        return ['day_of_week' => $day, 'opens_at' => $opens, 'closes_at' => $closes, 'is_active' => $active];
    }

    public function test_chambers_support_multiple_weekdays_and_separate_daily_shifts(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.content.store', 'chamber'), $this->data([
            $this->slot(6), $this->slot(1), $this->slot(3), $this->slot(1, '09:00', '12:00'), $this->slot(1, '10:00', '11:00', false),
        ]))->assertSessionHasNoErrors();
        $chamber = Chamber::firstOrFail();
        $this->assertSame(5, $chamber->schedules()->count());
        $this->assertSame('Asia/Dhaka', $chamber->timezone);
        $this->assertSame('clinic@example.com', $chamber->email);
        $this->assertSame(['01700000000', '01800000000'], $chamber->additional_phones);
        $this->assertSame('09:00', substr($chamber->schedules()->first()->opens_at, 0, 5));
        $this->get(route('admin.content.edit', ['chamber', $chamber->id]))->assertOk()->assertSee('Consultation hours');
    }

    public function test_overlapping_or_duplicate_slots_prevent_all_chamber_changes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $chamber = Chamber::factory()->create(['name' => 'Original name']);
        ChamberSchedule::factory()->for($chamber)->create();
        $data = $this->data([$this->slot(1, '17:00', '20:00'), $this->slot(1, '19:00', '21:00')]);
        $this->put(route('admin.content.update', ['chamber', $chamber->id]), $data)->assertSessionHasErrors('schedules.1.opens_at');
        $this->assertSame('Original name', $chamber->fresh()->name);
        $this->assertSame(1, $chamber->schedules()->count());
        $data['schedules'] = [$this->slot(1), $this->slot(1, '17:00', '18:00', false)];
        $this->put(route('admin.content.update', ['chamber', $chamber->id]), $data)->assertSessionHasErrors('schedules.1.opens_at');
    }

    public function test_bad_days_reversed_times_and_timezone_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->data([$this->slot(7, '21:00', '17:00')]);
        $data['timezone'] = 'Invalid/Timezone';
        $data['map_url'] = 'javascript:alert(1)';
        $data['email'] = 'not-an-email';
        $this->post(route('admin.content.store', 'chamber'), $data)->assertSessionHasErrors(['timezone', 'map_url', 'email', 'schedules.0.day_of_week', 'schedules.0.closes_at']);
        $this->assertSame(0, Chamber::count());
        $this->assertSame(0, ChamberSchedule::count());
    }

    public function test_schedule_updates_and_clearing_do_not_change_other_chambers(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $chamber = Chamber::factory()->create();
        $other = Chamber::factory()->create();
        ChamberSchedule::factory()->for($chamber)->create();
        $otherSlot = ChamberSchedule::factory()->for($other)->create();
        $this->put(route('admin.content.update', ['chamber', $chamber->id]), $this->data([$this->slot(3)]))->assertSessionHasNoErrors();
        $this->assertSame(3, $chamber->schedules()->first()->day_of_week);
        $this->assertModelExists($otherSlot);
        $clearData = $this->data();
        unset($clearData['schedules']);
        $this->put(route('admin.content.update', ['chamber', $chamber->id]), $clearData)->assertSessionHasNoErrors();
        $this->assertSame(0, $chamber->schedules()->count());
        $this->assertModelExists($otherSlot);
    }

    public function test_schedule_payload_cannot_reassign_existing_rows_to_another_chamber(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $chamber = Chamber::factory()->create();
        $otherSlot = ChamberSchedule::factory()->create();
        $data = $this->data([$this->slot(3) + ['id' => $otherSlot->id, 'chamber_id' => $otherSlot->chamber_id]]);
        $this->put(route('admin.content.update', ['chamber', $chamber->id]), $data)->assertSessionHasErrors('schedules.0');
        $this->assertModelExists($otherSlot);
        $this->assertSame(0, $chamber->schedules()->count());
    }

    public function test_chamber_schedules_survive_archive_and_restore_and_cascade_only_on_permanent_deletion(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $chamber = Chamber::factory()->published()->create();
        $schedule = ChamberSchedule::factory()->for($chamber)->create();
        $this->delete(route('admin.content.destroy', ['chamber', $chamber->id]))->assertRedirect();
        $this->assertModelExists($schedule);
        $this->post(route('admin.content.restore', ['chamber', $chamber->id]))->assertRedirect();
        $this->assertModelExists($schedule);
        $this->assertFalse($chamber->fresh()->is_active);
        $this->delete(route('admin.content.destroy', ['chamber', $chamber->id]));
        $this->delete(route('admin.content.force-destroy', ['chamber', $chamber->id]))->assertRedirect();
        $this->assertModelMissing($schedule);
    }
}
