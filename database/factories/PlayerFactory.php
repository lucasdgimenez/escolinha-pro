<?php

namespace Database\Factories;

use App\Enums\DominantFoot;
use App\Enums\Position;
use App\Models\Player;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        $birthYear = now()->year - fake()->numberBetween(5, 17);

        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => null,
            'name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween("{$birthYear}-01-01", "{$birthYear}-12-31")->format('Y-m-d'),
            'position' => fake()->randomElement(Position::cases())->value,
            'dominant_foot' => fake()->randomElement(DominantFoot::cases())->value,
            'photo_path' => null,
            'guardian_name' => fake()->name(),
            'guardian_email' => fake()->unique()->safeEmail(),
            'guardian_phone' => fake()->phoneNumber(),
        ];
    }
}
