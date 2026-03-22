<?php

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('director can access dashboard', function () {
    $user = User::factory()->director()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('coach can access dashboard', function () {
    $user = User::factory()->coach()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('super_admin can access dashboard', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('parent cannot access web panel and receives 403', function () {
    $user = User::factory()->guardian()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

it('player cannot access web panel and receives 403', function () {
    $user = User::factory()->player()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

it('director is redirected to dashboard after login', function () {
    $user = User::factory()->director()->create(['password' => bcrypt('password123')]);

    Livewire::test('pages::auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password123')
        ->call('login')
        ->assertRedirect(route('dashboard'));
});

it('coach is redirected to dashboard after login', function () {
    $user = User::factory()->coach()->create(['password' => bcrypt('password123')]);

    Livewire::test('pages::auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password123')
        ->call('login')
        ->assertRedirect(route('dashboard'));
});

it('parent is redirected to portal after login', function () {
    $user = User::factory()->guardian()->create(['password' => bcrypt('password123')]);

    Livewire::test('pages::auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password123')
        ->call('login')
        ->assertRedirect(route('portal'));
});

it('player is redirected to portal after login', function () {
    $user = User::factory()->player()->create(['password' => bcrypt('password123')]);

    Livewire::test('pages::auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password123')
        ->call('login')
        ->assertRedirect(route('portal'));
});

it('portal page renders for parent', function () {
    $user = User::factory()->guardian()->create();
    $this->actingAs($user)->get(route('portal'))->assertOk();
});
