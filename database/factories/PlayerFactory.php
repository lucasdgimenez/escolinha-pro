<?php

namespace Database\Factories;

use App\Enums\DominantFoot;
use App\Enums\PlayerPosition;
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
        return [
            'tenant_id'      => Tenant::factory(),
            'category_id'    => null,
            'name'           => fake()->name(),
            'date_of_birth'  => fake()->dateTimeBetween('-17 years', '-5 years')->format('Y-m-d'),
            'position'       => fake()->randomElement(PlayerPosition::cases()),
            'dominant_foot'  => fake()->randomElement(DominantFoot::cases()),
            'photo_path'     => null,
            'guardian_name'  => fake()->name(),
            'guardian_email' => fake()->unique()->safeEmail(),
            'guardian_phone' => fake()->phoneNumber(),
        ];
    }
}
