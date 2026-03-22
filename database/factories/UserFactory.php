<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'role_id' => Role::factory()->state(['slug' => RoleSlug::Coach->value, 'name' => 'Coach']),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleSlug::SuperAdmin->value],
                ['name' => 'Super Admin']
            )->id,
        ]);
    }

    public function director(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleSlug::AcademyDirector->value],
                ['name' => 'Diretor de Academia']
            )->id,
        ]);
    }

    public function coach(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleSlug::Coach->value],
                ['name' => 'Treinador']
            )->id,
        ]);
    }

    public function guardian(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleSlug::Parent->value],
                ['name' => 'Responsável']
            )->id,
        ]);
    }

    public function player(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::firstOrCreate(
                ['slug' => RoleSlug::Player->value],
                ['name' => 'Atleta']
            )->id,
        ]);
    }
}
