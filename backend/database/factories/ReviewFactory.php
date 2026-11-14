<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'workplace' => fake()->city(),
            'message' => fake()->paragraph(),
            'status' => ReviewStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Approved,
            'approved_at' => now(),
            'approved_by' => User::factory()->admin(),
        ]);
    }
}
