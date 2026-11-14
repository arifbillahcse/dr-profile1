<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_and_health_pages_are_available_without_an_admin_login(): void
    {
        $this->get('/')->assertOk()->assertSee('id="main-content"', false);
        $this->get('/up')->assertOk();
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_initial_seeding_is_repeatable_and_preserves_profile_edits_without_creating_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        DoctorProfile::where('key', 'primary')->firstOrFail()->update(['name' => 'Edited doctor name']);
        SiteSetting::where('key', 'primary')->firstOrFail()->update(['phone' => '01700000000']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, DoctorProfile::count());
        $this->assertSame(1, SiteSetting::count());
        $this->assertDatabaseHas('doctor_profiles', ['name' => 'Edited doctor name']);
        $this->assertDatabaseHas('site_settings', ['phone' => '01700000000']);
        $this->assertSame(0, User::count());
    }

    public function test_database_status_reports_the_configured_modules(): void
    {
        $this->artisan('project:status')
            ->expectsOutputToContain('Backend database is ready')
            ->expectsOutputToContain('Chamber schedules')
            ->expectsOutputToContain('Reviews')
            ->assertExitCode(0);
    }

    public function test_administrator_privilege_cannot_be_granted_by_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Website user',
            'email' => 'visitor@example.test',
            'password' => 'A-long-test-password',
            'is_admin' => true,
        ])->fresh();

        $this->assertFalse($user->is_admin);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_database_status_returns_failure_when_module_tables_are_missing(): void
    {
        Schema::shouldReceive('hasTable')->andReturn(false);

        $this->artisan('project:status')
            ->expectsOutputToContain('Missing tables:')
            ->assertExitCode(1);
    }
}
