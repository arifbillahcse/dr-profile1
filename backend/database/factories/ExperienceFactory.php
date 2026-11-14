<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Experience> */
class ExperienceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution' => fake()->company(),
            'position' => 'Assistant Professor',
            'started_on' => '2023-01-01',
            'is_current' => true,
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
