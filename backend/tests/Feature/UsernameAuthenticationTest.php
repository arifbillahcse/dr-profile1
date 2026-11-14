<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsernameAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_asks_for_username_and_rejects_email_only_authentication(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'clinic_admin']);
        $this->get(route('admin.login'))->assertOk()->assertSee('for="username"', false)->assertSee('name="username"', false)->assertDontSee('Email address')->assertDontSee('type="email"', false);
        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->post(route('admin.login.store'), ['username' => ' CLINIC_ADMIN ', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_new_administrators_can_be_created_without_any_email(): void
    {
        $this->artisan('admin:create', ['username' => ' Clinic-Owner ', '--name' => 'Clinic Owner'])
            ->expectsQuestion('Password', 'Initial-Strong-Password-42!')
            ->expectsQuestion('Confirm password', 'Initial-Strong-Password-42!')
            ->expectsOutput('Administrator created: clinic-owner')->assertSuccessful();
        $admin = User::where('username', 'clinic-owner')->firstOrFail();
        $this->assertNull($admin->email);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('Initial-Strong-Password-42!', $admin->password));
        $this->post(route('admin.login.store'), ['username' => 'clinic-owner', 'password' => 'Initial-Strong-Password-42!'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_renaming_a_username_preserves_password_and_privileges(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'old_admin', 'email' => 'admin@doctor.demo.softorio.com']);
        $hash = $admin->password;
        $this->artisan('admin:username', ['account' => $admin->email])->expectsOutput('Administrator username: old_admin')->assertSuccessful();
        $this->artisan('admin:username', ['account' => $admin->email, 'username' => ' Admin '])->expectsOutput('Administrator username: admin')->assertSuccessful();
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertTrue($admin->fresh()->is_admin);
        $this->post(route('admin.login.store'), ['username' => 'old_admin', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->post(route('admin.login.store'), ['username' => 'admin', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_username_command_cannot_promote_users_or_take_another_accounts_username(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'first_owner']);
        $other = User::factory()->admin()->create(['username' => 'second_owner']);
        $visitor = User::factory()->create(['username' => 'visitor']);
        $this->artisan('admin:username', ['account' => (string) $visitor->id, 'username' => 'promoted'])->assertFailed();
        $this->artisan('admin:username', ['account' => (string) $admin->id, 'username' => $other->username])->assertFailed();
        $this->artisan('admin:username', ['account' => (string) $admin->id, 'username' => 'bad username'])->assertFailed();
        $this->assertSame('first_owner', $admin->fresh()->username);
        $this->assertFalse($visitor->fresh()->is_admin);
    }

    public function test_duplicate_usernames_are_rejected_by_the_database(): void
    {
        User::factory()->create(['username' => 'same_owner']);
        $this->expectException(QueryException::class);
        User::factory()->create(['username' => 'same_owner']);
    }

    public function test_provisioning_cannot_reuse_a_username_or_create_an_invalid_username(): void
    {
        $user = User::factory()->create(['username' => 'taken_username']);
        foreach (['taken_username', 'ab', 'bad name', 'name@example.org'] as $username) {
            $this->artisan('admin:create', ['username' => $username, '--name' => 'Owner'])
                ->expectsQuestion('Password', 'Initial-Strong-Password-42!')
                ->expectsQuestion('Confirm password', 'Initial-Strong-Password-42!')->assertFailed();
        }
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertDatabaseCount('users', 1);
    }
}
