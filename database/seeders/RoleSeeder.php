<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => RoleSlug::SuperAdmin->value],
            ['name' => 'Diretor de Academia', 'slug' => RoleSlug::AcademyDirector->value],
            ['name' => 'Treinador', 'slug' => RoleSlug::Coach->value],
            ['name' => 'Responsável', 'slug' => RoleSlug::Parent->value],
            ['name' => 'Atleta', 'slug' => RoleSlug::Player->value],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
