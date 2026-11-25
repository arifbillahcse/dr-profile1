<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $profile = DB::table('doctor_profiles')->where('key', 'primary')
                ->where('name', 'Dr. Farzana Rahman Shathi')->lockForUpdate()->first();
            if (! $profile) {
                return;
            }
            $credentials = json_decode($profile->credentials ?? '[]', true);
            if (! is_array($credentials)) {
                return;
            }
            foreach ($credentials as $credential) {
                if (is_string($credential) && preg_match('/\bAAAM\b/i', $credential)) {
                    return;
                }
            }
            $credentials[] = 'Member of American Academy of Aesthetic Medicine (AAAM)';
            DB::table('doctor_profiles')->where('id', $profile->id)->update([
                'credentials' => json_encode($credentials, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve the membership correction and later administrator edits on rollback.
    }
};
