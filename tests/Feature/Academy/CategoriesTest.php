<?php

use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('default CBF categories are created when tenant is registered', function () {
    $director = User::factory()->director()->create();

    $categories = Category::withoutGlobalScopes()
        ->where('tenant_id', $director->tenant_id)
        ->orderBy('min_age')
        ->get();

    expect($categories)->toHaveCount(6);
    expect($categories->pluck('name')->toArray())->toBe([
        'Sub-7', 'Sub-9', 'Sub-11', 'Sub-13', 'Sub-15', 'Sub-17',
    ]);
});

it('categories page renders for director', function () {
    $director = User::factory()->director()->create();

    $this->actingAs($director)->get(route('academy.categories'))->assertOk();
});

it('director can create a new category', function () {
    $director = User::factory()->director()->create();

    Livewire::actingAs($director)
        ->test('pages::academy.categories')
        ->call('openCreate')
        ->set('name', 'Sub-20')
        ->set('minAge', 18)
        ->set('maxAge', 20)
        ->set('monthlyFee', '250.00')
        ->call('save')
        ->assertHasNoErrors();

    expect(
        Category::withoutGlobalScopes()
            ->where('tenant_id', $director->tenant_id)
            ->where('name', 'Sub-20')
            ->exists()
    )->toBeTrue();
});

it('director can edit a category name and monthly fee', function () {
    $director = User::factory()->director()->create();
    $category = Category::withoutGlobalScopes()
        ->where('tenant_id', $director->tenant_id)
        ->first();

    Livewire::actingAs($director)
        ->test('pages::academy.categories')
        ->call('edit', $category->id)
        ->set('name', 'Sub-7 Editado')
        ->set('monthlyFee', '150.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('Sub-7 Editado');
    expect((float) $category->fresh()->monthly_fee)->toBe(150.0);
});

it('director can toggle category active/inactive', function () {
    $director = User::factory()->director()->create();
    $category = Category::withoutGlobalScopes()
        ->where('tenant_id', $director->tenant_id)
        ->first();

    expect($category->is_active)->toBeTrue();

    Livewire::actingAs($director)
        ->test('pages::academy.categories')
        ->call('toggleActive', $category->id)
        ->assertHasNoErrors();

    expect($category->fresh()->is_active)->toBeFalse();
});

it('categories are scoped to tenant', function () {
    $directorA = User::factory()->director()->create();
    $directorB = User::factory()->director()->create();

    Category::withoutGlobalScopes()->create([
        'tenant_id'   => $directorA->tenant_id,
        'name'        => 'Categoria Exclusiva Tenant A',
        'min_age'     => 3,
        'max_age'     => 4,
        'monthly_fee' => 0,
    ]);

    app()->instance(\App\Models\Tenant::class, $directorB->tenant);

    Livewire::actingAs($directorB)
        ->test('pages::academy.categories')
        ->assertDontSee('Categoria Exclusiva Tenant A');
});

it('coach cannot access categories page', function () {
    $director = User::factory()->director()->create();
    $coach = User::factory()->coach()->create(['tenant_id' => $director->tenant_id]);

    $this->actingAs($coach)->get(route('academy.categories'))->assertForbidden();
});
