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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 40)->default('public');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['disk', 'path']);
            $table->timestamps();
        });

        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->default('primary');
            $table->string('name');
            $table->string('designation')->nullable();
            $table->json('credentials')->nullable();
            $table->text('summary')->nullable();
            $table->longText('biography')->nullable();
            $table->string('introduction_youtube_id', 11)->nullable();
            foreach (['portrait_image_id', 'first_image_id', 'second_image_id', 'tall_image_id'] as $column) {
                $table->foreignId($column)->nullable()->constrained('media_assets')->nullOnDelete();
            }
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->default('primary');
            $table->string('site_title');
            $table->foreignId('logo_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('favicon_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('phone', 50)->nullable();
            $table->json('additional_phones')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->json('social_links')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('doctor_profiles');
        Schema::dropIfExists('media_assets');
    }
};
