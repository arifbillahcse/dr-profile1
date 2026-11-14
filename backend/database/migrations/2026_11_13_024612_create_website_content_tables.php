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
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('featured_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('value', 50);
            $table->string('suffix', 20)->nullable();
            $table->string('icon', 100)->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon', 100)->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->string('qualification');
            $table->string('institution')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('organization')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('caption')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->longText('answer');
            $this->publicationColumns($table);
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('youtube_id', 11);
            $table->text('description')->nullable();
            $table->foreignId('thumbnail_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('institution');
            $table->string('position');
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publicationColumns($table);
        });

        Schema::create('consultation_steps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('icon', 100)->nullable();
            $this->publicationColumns($table);
        });

        Schema::create('specialist_highlights', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $this->publicationColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['specialist_highlights', 'consultation_steps', 'experiences', 'videos', 'faqs', 'gallery_images', 'awards', 'educations', 'services', 'featured_statistics', 'sliders'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function publicationColumns(Blueprint $table): void
    {
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(false);
        $table->index(['is_active', 'sort_order']);
        $table->timestamps();
        $table->softDeletes();
    }
};
