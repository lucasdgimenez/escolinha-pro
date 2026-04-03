<?php

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

    $this->director = User::factory()->director()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->coach = User::factory()->coach()->create([
        'tenant_id' => $this->tenant->id,
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

    Player::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category_id' => $sub11->id,
    ]);

    $service->syncCategories($this->coach, []);

    expect($this->coach->assignedCategories()->count())->toBe(0);
    expect(Player::count())->toBe(1);
    expect(Category::where('name', 'Sub-11')->exists())->toBeTrue();
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
    User::factory()->coach()->create(['tenant_id' => $otherTenant->id]);

    $coaches = $service->getCoachesWithCategories($this->tenant);

    expect($coaches)->toHaveCount(1);
    expect($coaches->first()->id)->toBe($this->coach->id);
});
