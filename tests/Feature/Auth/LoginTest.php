<?php

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('login page renders successfully', function () {
    $this->get(route('login'))->assertOk();
});

it('logs in with valid credentials and redirects to dashboard', function () {
    $user = User::factory()->director()->create(['password' => bcrypt('password123')]);

    Livewire::test('pages::auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('shows error for invalid password', function () {
    $user = User::factory()->director()->create(['password' => bcrypt('correct-password')]);

    Livewire::test('pages::auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['form.email']);

    $this->assertGuest();
});

it('shows error for non-existent email', function () {
    Livewire::test('pages::auth.login')
        ->set('form.email', 'nobody@example.com')
        ->set('form.password', 'password123')
        ->call('login')
        ->assertHasErrors(['form.email']);

    $this->assertGuest();
});

it('validates required fields', function () {
    Livewire::test('pages::auth.login')
        ->call('login')
        ->assertHasErrors(['form.email', 'form.password']);
});

it('redirects authenticated users away from login page', function () {
    $user = User::factory()->director()->create();

    $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
});

it('redirects unauthenticated users from dashboard to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
