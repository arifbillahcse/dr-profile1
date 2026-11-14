<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->string('specialty')->nullable();
            $table->string('trusted_patients', 50)->nullable();
        });
        Schema::table('site_settings', function (Blueprint $table) {
            $table->foreignId('social_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
        });
        Schema::table('experiences', function (Blueprint $table) {
            $table->string('period_label', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn('period_label');
        });
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('social_image_id');
        });
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->dropColumn(['specialty', 'trusted_patients']);
        });
    }
};
