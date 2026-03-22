<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DirectorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('slug', RoleSlug::AcademyDirector->value)->firstOrFail();

        $tenant = Tenant::firstOrCreate(
            ['name' => 'Academia Escolinha Teste'],
        );

        User::firstOrCreate(
            ['email' => 'diretor@escolinhateste.com.br'],
            [
                'name'              => 'Diretor Teste',
                'password'          => Hash::make('password'),
                'role_id'           => $role->id,
                'tenant_id'         => $tenant->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
