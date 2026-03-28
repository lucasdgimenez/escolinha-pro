<?php

use App\Enums\DominantFoot;
use App\Enums\Position;
use App\Enums\RoleSlug;
use App\Models\Category;
use App\Models\Player;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Academy\CoachAssignmentService;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

    $this->tenant = Tenant::factory()->create();
    app()->instance(Tenant::class, $this->tenant);

    $this->director = User::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'role_id' => Role::where('slug', RoleSlug::AcademyDirector->value)->value('id'),
        'name' => 'Diretor',
        'email' => 'diretor@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $this->coach = User::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'role_id' => Role::where('slug', RoleSlug::Coach->value)->value('id'),
        'name' => 'Treinador',
        'email' => 'treinador@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);
});

it('director can assign categories to a coach', function () {
    $service = app(CoachAssignmentService::class);

    $sub11 = Category::where('name', 'Sub-11')->first();
    $sub13 = Category::where('name', 'Sub-13')->first();

    $service->syncCategories($this->coach, [$sub11->id, $sub13->id]);

    expect($this->coach->assignedCategories()->count())->toBe(2);
    expect($this->coach->assignedCategories->pluck('id'))->toContain($sub11->id, $sub13->id);
});

it('sync replaces previous assignments', function () {
    $service = app(CoachAssignmentService::class);

    $sub11 = Category::where('name', 'Sub-11')->first();
    $sub13 = Category::where('name', 'Sub-13')->first();
    $sub15 = Category::where('name', 'Sub-15')->first();

    $service->syncCategories($this->coach, [$sub11->id, $sub13->id]);
    $service->syncCategories($this->coach, [$sub13->id, $sub15->id]);

    $assignedIds = $this->coach->assignedCategories()->pluck('id');

    expect($assignedIds)->toHaveCount(2);
    expect($assignedIds)->toContain($sub13->id, $sub15->id);
    expect($assignedIds)->not->toContain($sub11->id);
});

it('removing an assignment does not delete players or categories', function () {
    $service = app(CoachAssignmentService::class);

    $sub11 = Category::where('name', 'Sub-11')->first();

    $service->syncCategories($this->coach, [$sub11->id]);

    Player::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'category_id' => $sub11->id,
        'name' => 'Atleta',
        'date_of_birth' => now()->subYears(11)->format('Y-m-d'),
        'position' => Position::Midfielder->value,
        'dominant_foot' => DominantFoot::Right->value,
        'guardian_name' => 'Pai',
        'guardian_email' => 'pai@example.com',
        'guardian_phone' => null,
    ]);

    // Remove all assignments
    $service->syncCategories($this->coach, []);

    expect($this->coach->assignedCategories()->count())->toBe(0);
    expect(Player::withoutGlobalScopes()->count())->toBe(1);
    expect(Category::withoutGlobalScopes()->where('name', 'Sub-11')->exists())->toBeTrue();
});

it('coach sees only players in assigned categories', function () {
    $service = app(CoachAssignmentService::class);

    $sub11 = Category::where('name', 'Sub-11')->first();
    $sub13 = Category::where('name', 'Sub-13')->first();

    // Assign coach to Sub-11 only
    $service->syncCategories($this->coach, [$sub11->id]);

    // Create players in both categories
    Player::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'category_id' => $sub11->id,
        'name' => 'Sub-11 Atleta',
        'date_of_birth' => now()->subYears(11)->format('Y-m-d'),
        'position' => Position::Midfielder->value,
        'dominant_foot' => DominantFoot::Right->value,
        'guardian_name' => 'Pai Sub11',
        'guardian_email' => 'pai11@example.com',
        'guardian_phone' => null,
    ]);

    Player::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'category_id' => $sub13->id,
        'name' => 'Sub-13 Atleta',
        'date_of_birth' => now()->subYears(13)->format('Y-m-d'),
        'position' => Position::Defender->value,
        'dominant_foot' => DominantFoot::Left->value,
        'guardian_name' => 'Pai Sub13',
        'guardian_email' => 'pai13@example.com',
        'guardian_phone' => null,
    ]);

    $response = $this->actingAs($this->coach)->get(route('players.index'));

    $response->assertOk();
    $response->assertSee('Sub-11 Atleta');
    $response->assertDontSee('Sub-13 Atleta');
});

it('throws exception when trying to assign categories to a non-coach user', function () {
    $service = app(CoachAssignmentService::class);
    $sub11 = Category::where('name', 'Sub-11')->first();

    expect(fn () => $service->syncCategories($this->director, [$sub11->id]))
        ->toThrow(\InvalidArgumentException::class);
});

it('director can access coach assignments route', function () {
    $this->actingAs($this->director)
        ->get(route('coaches.assignments'))
        ->assertOk();
});

it('coach cannot access coach assignments route', function () {
    $this->actingAs($this->coach)
        ->get(route('coaches.assignments'))
        ->assertForbidden();
});

it('getCoachesWithCategories returns only coaches for the tenant', function () {
    $service = app(CoachAssignmentService::class);

    $otherTenant = Tenant::factory()->create();
    User::withoutGlobalScopes()->create([
        'tenant_id' => $otherTenant->id,
        'role_id' => Role::where('slug', RoleSlug::Coach->value)->value('id'),
        'name' => 'Outro Treinador',
        'email' => 'outro@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $coaches = $service->getCoachesWithCategories($this->tenant);

    expect($coaches)->toHaveCount(1);
    expect($coaches->first()->id)->toBe($this->coach->id);
});
