<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'invited_by' => User::factory()->director(),
            'email' => fake()->unique()->safeEmail(),
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleSlug::Coach->value],
                ['name' => 'Treinador']
            )->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
            'accepted_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now()->subMinutes(10),
        ]);
    }
}
