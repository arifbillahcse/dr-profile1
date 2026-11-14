<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Video> */
class VideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'youtube_id' => 'n7cZSkZiRbI',
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
