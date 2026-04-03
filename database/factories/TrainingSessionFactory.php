<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'category_id'      => Category::factory(),
            'schedule_id'      => null,
            'session_date'     => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time'       => fake()->randomElement(['08:00', '10:00', '14:00', '16:00', '18:00']),
            'duration_minutes' => fake()->randomElement([60, 90, 120]),
            'location'         => fake()->optional()->randomElement(['Campo 1', 'Campo 2', 'Ginásio']),
            'status'           => SessionStatus::Scheduled->value,
            'notes'            => null,
            'rating'           => null,
        ];
    }

    public function oneOff(): static
    {
        return $this->state(['schedule_id' => null]);
    }
}
