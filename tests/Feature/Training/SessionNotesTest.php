<?php

use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\TrainingSession;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $this->director = User::factory()->director()->create();
    app()->instance(Tenant::class, $this->director->tenant);

    $this->category = Category::factory()->create([
        'tenant_id' => $this->director->tenant_id,
        'is_active' => true,
    ]);

    $this->coach = User::factory()->coach()->create(['tenant_id' => $this->director->tenant_id]);
    $this->coach->assignedCategories()->attach($this->category->id);

    $this->session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::Completed->value,
        'notes'       => null,
        'rating'      => null,
    ]);
});

it('coach can save notes and rating on session', function () {
    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->set('notes', 'Treino muito produtivo.')
        ->set('rating', '4')
        ->call('saveNotes')
        ->assertHasNoErrors();

    $this->session->refresh();

    expect($this->session->notes)->toBe('Treino muito produtivo.')
        ->and($this->session->rating)->toBe(4);
});

it('saving with empty notes clears the field', function () {
    $this->session->update(['notes' => 'Nota antiga.', 'rating' => 3]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->set('notes', '')
        ->set('rating', '')
        ->call('saveNotes')
        ->assertHasNoErrors();

    $this->session->refresh();

    expect($this->session->notes)->toBeNull()
        ->and($this->session->rating)->toBeNull();
});

it('rating must be between 1 and 5', function () {
    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->set('rating', '6')
        ->call('saveNotes')
        ->assertHasErrors(['rating']);
});

it('parent cannot access session show page', function () {
    $parent = User::factory()->guardian()->create(['tenant_id' => $this->director->tenant_id]);

    $this->actingAs($parent)
        ->get(route('sessions.show', $this->session))
        ->assertForbidden();
});

it('player cannot access session show page', function () {
    $player = User::factory()->player()->create(['tenant_id' => $this->director->tenant_id]);

    $this->actingAs($player)
        ->get(route('sessions.show', $this->session))
        ->assertForbidden();
});
