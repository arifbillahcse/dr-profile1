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
            $site = DB::table('site_settings')->where('key', 'primary')->lockForUpdate()->first();
            if (! $site || ! DB::table('doctor_profiles')->where('key', 'primary')->where('name', 'Dr. Farzana Rahman Shathi')->exists()) {
                return;
            }
            $links = json_decode($site->social_links ?? '[]', true);
            if (! is_array($links) || ! is_string($links['facebook'] ?? null)) {
                return;
            }
            if (! in_array(rtrim($links['facebook'], '/'), ['https://www.facebook.com/dr.shathi', 'https://facebook.com/dr.shathi', 'http://www.facebook.com/dr.shathi', 'http://facebook.com/dr.shathi'], true)) {
                return;
            }
            $links['facebook'] = 'https://www.facebook.com/dr.farzanashathi';
            DB::table('site_settings')->where('id', $site->id)->update([
                'social_links' => json_encode($links, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep the corrected profile URL and any later administrator edits on rollback.
    }
};
