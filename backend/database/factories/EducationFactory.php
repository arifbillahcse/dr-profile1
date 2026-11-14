<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Education> */
class EducationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'qualification' => 'FCPS',
            'institution' => fake()->company(),
            'year' => 2023,
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
