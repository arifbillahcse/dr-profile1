<?php

namespace Database\Factories;

use App\Models\Chamber;
use App\Models\ChamberSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChamberSchedule> */
class ChamberScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chamber_id' => Chamber::factory(),
            'day_of_week' => 1,
            'opens_at' => '17:00:00',
            'closes_at' => '21:00:00',
            'is_active' => true,
        ];
    }
}
