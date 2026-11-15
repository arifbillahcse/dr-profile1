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
        $profile = DB::table('doctor_profiles')->where('key', 'primary')->first(['id', 'biography']);
        $originalName = 'ডাঃ ফারজানা রহমান সাথী';
        if ($profile && str_starts_with($profile->biography ?? '', $originalName)) {
            DB::table('doctor_profiles')->where('id', $profile->id)->where('biography', $profile->biography)->update([
                'biography' => 'Dr. Farzana Rahman Shathi'.substr($profile->biography, strlen($originalName)),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This editorial correction must not reverse later administrator edits.
    }
};
