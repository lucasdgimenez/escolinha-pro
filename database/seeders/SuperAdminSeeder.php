<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('slug', RoleSlug::SuperAdmin->value)->firstOrFail();

        User::firstOrCreate(
            ['email' => 'admin@escolinhapro.com.br'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'tenant_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
