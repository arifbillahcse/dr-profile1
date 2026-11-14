<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 64)->nullable()->unique();
            });
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });
        DB::table('users')->whereNull('username')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $base = trim(preg_replace('/[^a-z0-9._-]/', '', Str::lower(Str::before($user->email ?? '', '@'))), '.-_');
                $base = strlen($base) >= 3 ? substr($base, 0, 48) : 'user';
                $username = $base;
                $suffix = 2;
                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base.'-'.$suffix++;
                }
                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('email')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $base = 'restored-user-'.$user->id;
                $email = $base.'@admin.invalid';
                $suffix = 2;
                while (DB::table('users')->where('email', $email)->exists()) {
                    $email = $base.'-'.$suffix++.'@admin.invalid';
                }
                DB::table('users')->where('id', $user->id)->update(['email' => $email]);
            }
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
