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
        Schema::create('chambers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->string('phone', 50)->nullable();
            $table->json('additional_phones')->nullable();
            $table->text('map_url')->nullable();
            $table->string('timezone', 64)->default('Asia/Dhaka');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->index(['is_active', 'sort_order']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chamber_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamber_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_active')->default(true);
            $table->unique(['chamber_id', 'day_of_week', 'opens_at']);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('workplace')->nullable();
            $table->text('message');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('chamber_schedules');
        Schema::dropIfExists('chambers');
    }
};
