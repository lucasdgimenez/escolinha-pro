<?php

use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Training\TrainingSessionService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $this->director = User::factory()->director()->create();
    app()->instance(Tenant::class, $this->director->tenant);

    $this->category = Category::factory()->create([
        'tenant_id' => $this->director->tenant_id,
        'is_active' => true,
        'min_age'   => 10,
        'max_age'   => 11,
    ]);

    $this->coach = User::factory()->coach()->create(['tenant_id' => $this->director->tenant_id]);
    $this->coach->assignedCategories()->attach($this->category->id);
});

it('director can view all sessions on index page', function () {
    $otherCategory = Category::factory()->create(['tenant_id' => $this->director->tenant_id, 'is_active' => true]);

    $session1 = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
    ]);

    $session2 = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $otherCategory->id,
    ]);

    Livewire::actingAs($this->director)
        ->test('pages::sessions.index')
        ->assertSee($session1->session_date->format('d/m/Y'))
        ->assertSee($session2->session_date->format('d/m/Y'));
});

it('coach sees only sessions for assigned categories on index', function () {
    $unassignedCategory = Category::factory()->create(['tenant_id' => $this->director->tenant_id, 'is_active' => true]);

    $assignedSession = TrainingSession::factory()->create([
        'tenant_id'    => $this->director->tenant_id,
        'category_id'  => $this->category->id,
        'session_date' => '2026-05-10',
    ]);

    TrainingSession::factory()->create([
        'tenant_id'    => $this->director->tenant_id,
        'category_id'  => $unassignedCategory->id,
        'session_date' => '2026-05-15',
    ]);

    $component = Livewire::actingAs($this->coach)
        ->test('pages::sessions.index');

    expect($component->instance()->sessions)->toHaveCount(1)
        ->and($component->instance()->sessions->first()->id)->toBe($assignedSession->id);
});

it('coach cannot view session show for unassigned category', function () {
    $otherCategory = Category::factory()->create(['tenant_id' => $this->director->tenant_id]);
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $otherCategory->id,
    ]);

    $this->actingAs($this->coach)
        ->get(route('sessions.show', $session))
        ->assertForbidden();
});

it('director can view session show page', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
    ]);

    Livewire::actingAs($this->director)
        ->test('pages::sessions.show', ['session' => $session])
        ->assertOk()
        ->assertSee($session->session_date->format('d/m/Y'));
});

it('coach can start a scheduled session', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::Scheduled->value,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $session])
        ->call('startSession')
        ->assertHasNoErrors();

    expect($session->fresh()->status)->toBe(SessionStatus::InProgress);
});

it('coach can complete an in-progress session', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::InProgress->value,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $session])
        ->call('completeSession')
        ->assertHasNoErrors();

    expect($session->fresh()->status)->toBe(SessionStatus::Completed);
});

it('coach can cancel a scheduled session', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::Scheduled->value,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $session])
        ->call('cancelSession')
        ->assertHasNoErrors();

    expect($session->fresh()->status)->toBe(SessionStatus::Cancelled);
});

it('coach can cancel an in-progress session', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::InProgress->value,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $session])
        ->call('cancelSession')
        ->assertHasNoErrors();

    expect($session->fresh()->status)->toBe(SessionStatus::Cancelled);
});

it('cannot transition from a completed session', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::Completed->value,
    ]);

    $service = app(TrainingSessionService::class);

    expect(fn () => $service->transitionTo($session, SessionStatus::InProgress))
        ->toThrow(InvalidArgumentException::class);
});

it('cannot transition from a cancelled session', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::Cancelled->value,
    ]);

    $service = app(TrainingSessionService::class);

    expect(fn () => $service->transitionTo($session, SessionStatus::Scheduled))
        ->toThrow(InvalidArgumentException::class);
});

it('sessions are scoped to tenant on index', function () {
    $mySession = TrainingSession::factory()->create([
        'tenant_id'    => $this->director->tenant_id,
        'category_id'  => $this->category->id,
        'session_date' => '2026-06-02',
    ]);

    // Create another tenant's session (bypassing global scope)
    $otherTenant = Tenant::factory()->create();
    $otherCategory = Category::withoutGlobalScopes()->create([
        'tenant_id' => $otherTenant->id,
        'name'      => 'Outra Sub-11',
        'min_age'   => 10,
        'max_age'   => 11,
        'is_active' => true,
    ]);
    $otherSession = TrainingSession::withoutGlobalScopes()->create([
        'tenant_id'        => $otherTenant->id,
        'category_id'      => $otherCategory->id,
        'session_date'     => '2026-06-01',
        'start_time'       => '10:00',
        'duration_minutes' => 60,
        'status'           => SessionStatus::Scheduled->value,
    ]);

    $component = Livewire::actingAs($this->director)
        ->test('pages::sessions.index');

    $ids = $component->instance()->sessions->pluck('id');

    expect($ids)->toContain($mySession->id)
        ->and($ids)->not->toContain($otherSession->id);
});

it('category filter works on sessions index', function () {
    $otherCategory = Category::factory()->create(['tenant_id' => $this->director->tenant_id, 'is_active' => true]);

    $sessionA = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
    ]);

    TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $otherCategory->id,
    ]);

    $component = Livewire::actingAs($this->director)
        ->test('pages::sessions.index')
        ->set('categoryId', (string) $this->category->id);

    $ids = $component->instance()->sessions->pluck('id');

    expect($ids)->toHaveCount(1)
        ->and($ids)->toContain($sessionA->id);
});

it('sessions index defaults to list view', function () {
    Livewire::actingAs($this->director)
        ->test('pages::sessions.index')
        ->assertSet('view', 'list')
        ->assertSee('Calendário');
});

it('calendar view shows sessions on correct date', function () {
    $session = TrainingSession::factory()->create([
        'tenant_id'    => $this->director->tenant_id,
        'category_id'  => $this->category->id,
        'session_date' => now()->format('Y-m-d'),
    ]);

    $component = Livewire::actingAs($this->director)
        ->test('pages::sessions.index')
        ->set('view', 'calendar');

    $dateKey = $session->session_date->format('Y-m-d');

    expect($component->instance()->sessionsByDate->has($dateKey))->toBeTrue()
        ->and($component->instance()->sessionsByDate->get($dateKey)->first()->id)->toBe($session->id);
});

it('calendar month navigation updates the displayed month', function () {
    $component = Livewire::actingAs($this->director)
        ->test('pages::sessions.index')
        ->set('view', 'calendar');

    $initialMonth = $component->get('currentMonth');
    $initialYear  = $component->get('currentYear');

    $component->call('nextMonth');

    $expectedMonth = $initialMonth === 12 ? 1 : $initialMonth + 1;
    $expectedYear  = $initialMonth === 12 ? $initialYear + 1 : $initialYear;

    expect($component->get('currentMonth'))->toBe($expectedMonth)
        ->and($component->get('currentYear'))->toBe($expectedYear);

    $component->call('previousMonth')->call('previousMonth');

    $expectedFinal = $initialMonth === 1 ? 12 : $initialMonth - 1;

    expect($component->get('currentMonth'))->toBe($expectedFinal);
});
