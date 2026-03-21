<?php

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('registration page renders successfully', function () {
    $this->get(route('register'))->assertOk();
});

it('creates a tenant and director user on successful registration', function () {
    Notification::fake();

    Livewire::test('pages::auth.register')
        ->set('form.name', 'João Silva')
        ->set('form.email', 'joao@academia.com')
        ->set('form.academy_name', 'Academia do João')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    expect(Tenant::where('name', 'Academia do João')->exists())->toBeTrue();
    expect(User::withoutGlobalScopes()->where('email', 'joao@academia.com')->exists())->toBeTrue();
});

it('logs the user in after registration', function () {
    Notification::fake();

    Livewire::test('pages::auth.register')
        ->set('form.name', 'João Silva')
        ->set('form.email', 'joao@academia.com')
        ->set('form.academy_name', 'Academia do João')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register');

    $this->assertAuthenticated();
});

it('shows validation error for duplicate email and creates no orphan tenant', function () {
    $existingUser = User::factory()->director()->create(['email' => 'joao@academia.com']);

    $tenantCountBefore = Tenant::withoutGlobalScopes()->count();

    Livewire::test('pages::auth.register')
        ->set('form.name', 'João Silva')
        ->set('form.email', 'joao@academia.com')
        ->set('form.academy_name', 'Nova Academia')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['form.email']);

    expect(Tenant::withoutGlobalScopes()->count())->toBe($tenantCountBefore);
});

it('validates all required fields', function () {
    Livewire::test('pages::auth.register')
        ->call('register')
        ->assertHasErrors(['form.name', 'form.email', 'form.academy_name', 'form.password']);
});

it('validates password confirmation', function () {
    Livewire::test('pages::auth.register')
        ->set('form.name', 'João Silva')
        ->set('form.email', 'joao@academia.com')
        ->set('form.academy_name', 'Academia do João')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'wrong-password')
        ->call('register')
        ->assertHasErrors(['form.password']);
});

it('sends VerifyEmailNotification after registration', function () {
    Notification::fake();

    Livewire::test('pages::auth.register')
        ->set('form.name', 'João Silva')
        ->set('form.email', 'joao@academia.com')
        ->set('form.academy_name', 'Academia do João')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register');

    $user = User::withoutGlobalScopes()->where('email', 'joao@academia.com')->firstOrFail();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});
