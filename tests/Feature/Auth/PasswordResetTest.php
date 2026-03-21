<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('forgot password page renders successfully', function () {
    $this->get(route('password.request'))->assertOk();
});

it('sends ResetPasswordNotification for valid email', function () {
    Notification::fake();

    $user = User::factory()->director()->create();

    Livewire::test('pages::auth.forgot-password')
        ->set('form.email', $user->email)
        ->call('sendResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('shows no error for non-existent email and sends no notification', function () {
    Notification::fake();

    Livewire::test('pages::auth.forgot-password')
        ->set('form.email', 'nobody@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors();

    Notification::assertNothingSent();
});

it('validates email field on forgot password form', function () {
    Livewire::test('pages::auth.forgot-password')
        ->call('sendResetLink')
        ->assertHasErrors(['form.email']);
});

it('reset password page renders successfully', function () {
    $this->get(route('password.reset', ['token' => 'some-token']))->assertOk();
});

it('resets password with valid token and redirects to login', function () {
    $user = User::factory()->director()->create();
    $token = Password::createToken($user);

    Livewire::test('pages::auth.reset-password', ['token' => $token])
        ->set('form.email', $user->email)
        ->set('form.password', 'new-password123')
        ->set('form.password_confirmation', 'new-password123')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(auth()->attempt(['email' => $user->email, 'password' => 'new-password123']))->toBeTrue();
});

it('shows error for invalid token', function () {
    $user = User::factory()->director()->create();

    Livewire::test('pages::auth.reset-password', ['token' => 'invalid-token'])
        ->set('form.email', $user->email)
        ->set('form.password', 'new-password123')
        ->set('form.password_confirmation', 'new-password123')
        ->call('resetPassword')
        ->assertHasErrors(['form.email']);
});
