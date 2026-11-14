<?php

namespace Database\Factories;

use App\Models\Award;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Award> */
class AwardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'organization' => fake()->company(),
            'year' => 2019,
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
