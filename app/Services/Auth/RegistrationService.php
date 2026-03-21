<?php

namespace App\Services\Auth;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function register(string $name, string $email, string $password, string $academyName): User
    {
        return DB::transaction(function () use ($name, $email, $password, $academyName) {
            $tenant = Tenant::create(['name' => $academyName]);
            $role = Role::where('slug', RoleSlug::AcademyDirector->value)->firstOrFail();

            return User::create([
                'tenant_id' => $tenant->id,
                'role_id' => $role->id,
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
        });
    }
}
