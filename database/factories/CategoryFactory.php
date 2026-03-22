<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $minAge = fake()->numberBetween(5, 15);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Sub-'.($minAge + 2),
            'min_age' => $minAge,
            'max_age' => $minAge + 2,
            'monthly_fee' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
