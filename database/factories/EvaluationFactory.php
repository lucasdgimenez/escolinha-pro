<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'player_id'    => Player::factory(),
            'coach_id'     => User::factory(),
            'category_id'  => Category::factory(),
            'evaluated_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'notes'        => null,
        ];
    }
}
