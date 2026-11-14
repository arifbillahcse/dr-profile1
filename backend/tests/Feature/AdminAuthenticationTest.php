<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_every_admin_section(): void
    {
        foreach (['/admin/dashboard', '/admin/password', '/admin/modules/setting'] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
        $this->get('/admin/login')->assertOk()->assertSee('Welcome back');
        $this->get('/register')->assertNotFound();
        $this->get('/admin/logout')->assertMethodNotAllowed();
    }

    public function test_admin_can_sign_in_with_a_normalized_username_and_sign_out(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'admin', 'email' => 'admin@example.test']);
        $this->post(route('admin.login.store'), ['username' => ' ADMIN ', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_invalid_credentials_and_non_admin_accounts_cannot_sign_in(): void
    {
        User::factory()->create(['username' => 'visitor', 'email' => 'visitor@example.test']);
        $this->post(route('admin.login.store'), ['username' => 'visitor', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $admin = User::factory()->admin()->create();
        $this->post(route('admin.login.store'), ['username' => $admin->username, 'password' => 'wrong-password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_failed_login_attempts_are_throttled_even_with_a_correct_password_after_lockout(): void
    {
        $this->freezeTime();
        $admin = User::factory()->admin()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.store'), ['username' => $admin->username, 'password' => 'wrong-password'])->assertSessionHasErrors('username');
        }
        $this->post(route('admin.login.store'), ['username' => $admin->username, 'password' => 'password'])
            ->assertSessionHasErrors(['username' => 'Too many sign-in attempts. Try again in 60 seconds.']);
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post(route('admin.login.store'), ['username' => $admin->username, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_and_revoked_admin_sessions_are_forbidden(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.password.edit'))->assertForbidden();
        $this->get(route('admin.modules.show', 'setting'))->assertForbidden();
    }

    public function test_password_change_requires_the_current_password_and_a_strong_confirmed_password(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.password.update'), [
            'current_password' => 'wrong', 'password' => 'New-Strong-Password-42!', 'password_confirmation' => 'New-Strong-Password-42!',
        ])->assertSessionHasErrors('current_password');
        $this->put(route('admin.password.update'), [
            'current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'mismatch',
        ])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_password_change_hashes_the_password_and_requires_a_new_login(): void
    {
        $admin = User::factory()->admin()->create();
        $newPassword = 'New-Strong-Password-42!';
        $this->actingAs($admin)->get(route('admin.password.edit'))->assertOk();
        $this->put(route('admin.password.update'), [
            'current_password' => 'password', 'password' => $newPassword, 'password_confirmation' => $newPassword,
        ])->assertRedirect(route('admin.login'))->assertSessionHas('status');
        $this->assertGuest();
        $this->assertTrue(Hash::check($newPassword, $admin->fresh()->password));
        $this->post(route('admin.login.store'), ['username' => $admin->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->post(route('admin.login.store'), ['username' => $admin->username, 'password' => $newPassword])->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_session_with_an_old_password_hash_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $oldHash = $admin->password;
        $admin->update(['password' => 'Changed-Password-123!']);
        $this->actingAs($admin)->withSession(['password_hash_web' => $oldHash])
            ->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_password_change_revokes_database_sessions_only_for_the_account_owner(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        foreach (['owner-session' => $admin->id, 'other-session' => $otherAdmin->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => base64_encode(serialize([])), 'last_activity' => time()]);
        }

        $this->actingAs($admin)->put(route('admin.password.update'), [
            'current_password' => 'password', 'password' => 'New-Strong-Password-42!', 'password_confirmation' => 'New-Strong-Password-42!',
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('sessions', ['user_id' => $admin->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session', 'user_id' => $otherAdmin->id]);
    }

    public function test_administrator_creation_hashes_password_and_does_not_print_it(): void
    {
        $this->artisan('admin:create', ['username' => 'owner', '--email' => 'owner@example.test', '--name' => 'Website Owner'])
            ->expectsQuestion('Password', 'Initial-Strong-Password-42!')
            ->expectsQuestion('Confirm password', 'Initial-Strong-Password-42!')
            ->expectsOutput('Administrator created: owner')
            ->doesntExpectOutput('Initial-Strong-Password-42!')
            ->assertSuccessful();
        $admin = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('Initial-Strong-Password-42!', $admin->password));
    }

    public function test_provisioning_does_not_promote_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);
        $this->artisan('admin:create', ['username' => 'owner', '--email' => $user->email, '--name' => 'Owner'])
            ->expectsQuestion('Password', 'Initial-Strong-Password-42!')
            ->expectsQuestion('Confirm password', 'Initial-Strong-Password-42!')
            ->assertFailed();
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertSame(1, User::count());
    }
}
