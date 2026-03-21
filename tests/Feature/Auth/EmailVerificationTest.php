<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

it('unverified user is redirected from dashboard to verification notice', function () {
    $user = User::factory()->director()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('verification notice page renders successfully', function () {
    $user = User::factory()->director()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk();
});

it('valid signed URL marks email as verified and redirects to dashboard', function () {
    Event::fake();

    $user = User::factory()->director()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class);
});

it('returns 403 for invalid hash', function () {
    $user = User::factory()->director()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $this->actingAs($user)
        ->get($url)
        ->assertForbidden();
});

it('resend action sends VerifyEmailNotification', function () {
    Notification::fake();

    $user = User::factory()->director()->unverified()->create();

    Livewire::actingAs($user)
        ->test('pages::auth.verify-email')
        ->call('resend');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('already-verified user is redirected from verification notice to dashboard', function () {
    $user = User::factory()->director()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});
