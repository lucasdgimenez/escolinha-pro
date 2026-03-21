<?php

use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('logs out authenticated user and redirects to login', function () {
    $user = User::factory()->director()->create();

    $this->withoutMiddleware();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('dashboard redirects to login after logout', function () {
    $user = User::factory()->director()->create();

    $this->withoutMiddleware();
    $this->actingAs($user)->post(route('logout'));

    $this->refreshApplication();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('unauthenticated logout redirects to login without crashing', function () {
    $this->withoutMiddleware();

    $this->post(route('logout'))->assertRedirect(route('login'));
});
