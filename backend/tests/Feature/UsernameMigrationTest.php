<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsernameMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private function usernameMigration(): Migration
    {
        return require database_path('migrations/2026_11_14_185258_add_username_to_users_table.php');
    }

    public function test_legacy_email_accounts_get_distinct_usernames_without_changing_passwords_or_roles(): void
    {
        $first = User::factory()->admin()->create(['email' => 'admin@doctor.demo.softorio.com']);
        $second = User::factory()->admin()->create(['email' => 'admin@another.example.org']);
        $third = User::factory()->create(['email' => 'admin-2@another.example.org']);
        $fourth = User::factory()->create(['email' => 'xy@another.example.org']);
        $hash = $first->password;
        $migration = $this->usernameMigration();
        $migration->down();
        $migration->up();
        $this->assertSame('admin', $first->fresh()->username);
        $this->assertSame('admin-2', $second->fresh()->username);
        $this->assertSame('admin-2-2', $third->fresh()->username);
        $this->assertSame('user', $fourth->fresh()->username);
        $this->assertSame($hash, $first->fresh()->password);
        $this->assertTrue($first->fresh()->is_admin);
        $this->assertFalse($third->fresh()->is_admin);
        $this->post(route('admin.login.store'), ['username' => 'admin', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $migration->up();
        $this->assertSame('admin', $first->fresh()->username);
        $this->assertDatabaseCount('users', 4);
    }

    public function test_optional_email_and_migration_rollback_preserve_account_passwords(): void
    {
        $first = User::factory()->admin()->create(['email' => null, 'username' => 'first_owner']);
        $second = User::factory()->admin()->create(['email' => null, 'username' => 'second_owner']);
        $this->assertTrue(Hash::check('password', $first->password));
        $this->usernameMigration()->down();
        $this->assertNotNull(DB::table('users')->where('id', $first->id)->value('email'));
        $this->assertNotSame(DB::table('users')->where('id', $first->id)->value('email'), DB::table('users')->where('id', $second->id)->value('email'));
        $this->assertSame($first->password, DB::table('users')->where('id', $first->id)->value('password'));
        $this->usernameMigration()->up();
        $this->assertNotEmpty($first->fresh()->username);
    }
}
