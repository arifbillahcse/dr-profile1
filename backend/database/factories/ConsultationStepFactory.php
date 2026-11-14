<?php

namespace Database\Factories;

use App\Models\ConsultationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConsultationStep> */
class ConsultationStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
