<?php

use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    Notification::fake();
});

it('director can send a coach invitation', function () {
    $director = User::factory()->director()->create();

    Livewire::actingAs($director)
        ->test('pages::invitations.index')
        ->set('form.email', 'treinador@academia.com')
        ->call('invite')
        ->assertHasNoErrors();

    expect(Invitation::withoutGlobalScopes()->where('email', 'treinador@academia.com')->exists())->toBeTrue();

    Notification::assertSentOnDemand(InvitationNotification::class);
});

it('cannot invite an already-registered email', function () {
    $director = User::factory()->director()->create();
    User::factory()->coach()->create([
        'tenant_id' => $director->tenant_id,
        'email' => 'already@academia.com',
    ]);

    Livewire::actingAs($director)
        ->test('pages::invitations.index')
        ->set('form.email', 'already@academia.com')
        ->call('invite')
        ->assertHasErrors(['form.email']);
});

it('acceptance page shows email read-only and role name for valid token', function () {
    $invitation = Invitation::factory()->create();

    Livewire::test('pages::auth.accept-invitation', ['token' => $invitation->token])
        ->assertSet('errorMessage', '')
        ->assertSeeHtml($invitation->email)
        ->assertSee($invitation->role->name);
});

it('accepting a valid invitation creates the user with correct role and tenant', function () {
    $invitation = Invitation::factory()->create();

    Livewire::test('pages::auth.accept-invitation', ['token' => $invitation->token])
        ->set('form.name', 'Treinador Silva')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user = User::withoutGlobalScopes()->where('email', $invitation->email)->firstOrFail();

    expect($user->tenant_id)->toBe($invitation->tenant_id);
    expect($user->role_id)->toBe($invitation->role_id);
    expect($user->email_verified_at)->not->toBeNull();

    $invitation->refresh();
    expect($invitation->accepted_at)->not->toBeNull();
});

it('accepted invitation token cannot be reused', function () {
    $invitation = Invitation::factory()->accepted()->create();

    Livewire::test('pages::auth.accept-invitation', ['token' => $invitation->token])
        ->assertSet('errorMessage', 'Este convite já foi utilizado.');
});

it('expired invitation token returns error message', function () {
    $invitation = Invitation::factory()->expired()->create();

    Livewire::test('pages::auth.accept-invitation', ['token' => $invitation->token])
        ->assertSet('errorMessage', 'Este convite expirou. Solicite um novo convite.');
});

it('invalid invitation token returns error message', function () {
    Livewire::test('pages::auth.accept-invitation', ['token' => 'invalid-token'])
        ->assertSet('errorMessage', 'Convite não encontrado.');
});

it('director can resend a pending invitation', function () {
    $director = User::factory()->director()->create();
    $invitation = Invitation::factory()->create(['tenant_id' => $director->tenant_id]);

    Livewire::actingAs($director)
        ->test('pages::invitations.index')
        ->call('resend', $invitation->id)
        ->assertHasNoErrors();

    Notification::assertSentOnDemand(InvitationNotification::class);
});

it('resend updates the token and extends expiry', function () {
    $director = User::factory()->director()->create();
    $invitation = Invitation::factory()->create([
        'tenant_id' => $director->tenant_id,
        'expires_at' => now()->subHours(1),
    ]);

    $oldToken = $invitation->token;

    Livewire::actingAs($director)
        ->test('pages::invitations.index')
        ->call('resend', $invitation->id);

    $invitation->refresh();

    expect($invitation->token)->not->toBe($oldToken);
    expect($invitation->expires_at->isFuture())->toBeTrue();
});
