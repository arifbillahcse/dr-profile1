<?php

namespace Database\Factories;

use App\Models\Chamber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Chamber> */
class ChamberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'timezone' => 'Asia/Dhaka',
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
