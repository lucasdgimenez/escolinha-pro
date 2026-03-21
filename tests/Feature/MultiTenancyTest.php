<?php

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    // Ensure roles are seeded for each test
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('seeds all required roles', function () {
    expect(Role::count())->toBe(5);

    foreach (RoleSlug::cases() as $case) {
        expect(Role::where('slug', $case->value)->exists())->toBeTrue();
    }
});

it('super_admin has no tenant_id', function () {
    $superAdminRole = Role::where('slug', RoleSlug::SuperAdmin->value)->first();
    $superAdmin = User::factory()->create([
        'tenant_id' => null,
        'role_id' => $superAdminRole->id,
    ]);

    expect($superAdmin->tenant_id)->toBeNull();
    expect($superAdmin->isSuperAdmin())->toBeTrue();
});

it('regular users must belong to a tenant', function () {
    $tenant = Tenant::factory()->create();
    $coachRole = Role::where('slug', RoleSlug::Coach->value)->first();

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role_id' => $coachRole->id,
    ]);

    expect($user->tenant_id)->toBe($tenant->id);
    expect($user->tenant->id)->toBe($tenant->id);
});

it('tenant scope filters users to the current tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $coachRole = Role::where('slug', RoleSlug::Coach->value)->first();

    // Create users without the global scope active (withoutGlobalScopes)
    User::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach A',
        'email' => 'coach@tenant-a.com',
        'password' => 'password',
    ]);

    User::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach B',
        'email' => 'coach@tenant-b.com',
        'password' => 'password',
    ]);

    // Bind tenant A as current tenant
    app()->instance(Tenant::class, $tenantA);

    $visibleUsers = User::all();

    expect($visibleUsers)->toHaveCount(1);
    expect($visibleUsers->first()->email)->toBe('coach@tenant-a.com');
});

it('users cannot access data from another tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $coachRole = Role::where('slug', RoleSlug::Coach->value)->first();

    User::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach A',
        'email' => 'coach@tenant-a.com',
        'password' => 'password',
    ]);

    $userB = User::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach B',
        'email' => 'coach@tenant-b.com',
        'password' => 'password',
    ]);

    // Bind tenant A — user from tenant B should not be visible
    app()->instance(Tenant::class, $tenantA);

    expect(User::where('id', $userB->id)->exists())->toBeFalse();
});

it('super_admin sees all tenants data when no tenant is bound', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $coachRole = Role::where('slug', RoleSlug::Coach->value)->first();

    User::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach A',
        'email' => 'coach@tenant-a.com',
        'password' => 'password',
    ]);

    User::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach B',
        'email' => 'coach@tenant-b.com',
        'password' => 'password',
    ]);

    // No tenant bound (super_admin context) — all users visible
    expect(User::count())->toBe(2);
});

it('resolve tenant middleware sets current tenant from authenticated user', function () {
    $tenant = Tenant::factory()->create();
    $coachRole = Role::where('slug', RoleSlug::Coach->value)->first();

    $user = User::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'role_id' => $coachRole->id,
        'name' => 'Coach',
        'email' => 'coach@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($user)->get('/healthz');

    expect(app()->bound(Tenant::class))->toBeTrue();
    expect(app(Tenant::class)->id)->toBe($tenant->id);
});

it('resolve tenant middleware does not bind tenant for super_admin', function () {
    $superAdminRole = Role::where('slug', RoleSlug::SuperAdmin->value)->first();

    $superAdmin = User::withoutGlobalScopes()->create([
        'tenant_id' => null,
        'role_id' => $superAdminRole->id,
        'name' => 'Super Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($superAdmin)->get('/healthz');

    expect(app()->bound(Tenant::class))->toBeFalse();
});
