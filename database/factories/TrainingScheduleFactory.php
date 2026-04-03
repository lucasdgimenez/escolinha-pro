<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\TrainingSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSchedule>
 */
class TrainingScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'category_id'      => Category::factory(),
            'day_of_week'      => fake()->randomElement(DayOfWeek::cases())->value,
            'start_time'       => fake()->randomElement(['08:00', '10:00', '14:00', '16:00', '18:00']),
            'duration_minutes' => fake()->randomElement([60, 90, 120]),
            'location'         => fake()->optional()->randomElement(['Campo 1', 'Campo 2', 'Ginásio']),
            'is_active'        => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
