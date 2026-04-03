<?php

use App\Enums\DayOfWeek;
use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\TrainingSchedule;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Training\TrainingScheduleService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->director = User::factory()->director()->create();
    $this->category = Category::factory()->create(['tenant_id' => $this->director->tenant_id]);
});

it('director can create a recurring schedule', function () {
    Livewire::actingAs($this->director)
        ->test('pages::schedules.create')
        ->set('form.category_id', $this->category->id)
        ->set('form.day_of_week', DayOfWeek::Monday->value)
        ->set('form.start_time', '16:00')
        ->set('form.duration_minutes', 90)
        ->set('form.location', 'Campo 1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('schedules.index'));

    expect(TrainingSchedule::where('category_id', $this->category->id)->exists())->toBeTrue();
});

it('recurring sessions are generated from an active schedule', function () {
    $schedule = TrainingSchedule::factory()->create([
        'tenant_id'        => $this->director->tenant_id,
        'category_id'      => $this->category->id,
        'day_of_week'      => DayOfWeek::Monday->value,
        'start_time'       => '16:00',
        'duration_minutes' => 90,
        'is_active'        => true,
    ]);

    $service = app(TrainingScheduleService::class);
    $service->generateUpcomingSessions(7);

    expect(TrainingSession::where('schedule_id', $schedule->id)->count())->toBeGreaterThanOrEqual(1);
});

it('does not create duplicate sessions for same schedule and date', function () {
    $schedule = TrainingSchedule::factory()->create([
        'tenant_id'        => $this->director->tenant_id,
        'category_id'      => $this->category->id,
        'day_of_week'      => DayOfWeek::Monday->value,
        'start_time'       => '16:00',
        'duration_minutes' => 90,
        'is_active'        => true,
    ]);

    $service = app(TrainingScheduleService::class);
    $service->generateUpcomingSessions(7);
    $countAfterFirst = TrainingSession::where('schedule_id', $schedule->id)->count();

    $service->generateUpcomingSessions(7);
    $countAfterSecond = TrainingSession::where('schedule_id', $schedule->id)->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});

it('pausing a schedule stops future session generation without affecting existing sessions', function () {
    $schedule = TrainingSchedule::factory()->create([
        'tenant_id'        => $this->director->tenant_id,
        'category_id'      => $this->category->id,
        'day_of_week'      => DayOfWeek::Monday->value,
        'start_time'       => '16:00',
        'duration_minutes' => 90,
        'is_active'        => true,
    ]);

    $service = app(TrainingScheduleService::class);
    $service->generateUpcomingSessions(7);
    $existingCount = TrainingSession::where('schedule_id', $schedule->id)->count();

    $service->pause($schedule);
    $service->generateUpcomingSessions(14);

    expect(TrainingSession::where('schedule_id', $schedule->id)->count())->toBe($existingCount);
});

it('coach can create a one-off session', function () {
    $coach = User::factory()->coach()->create(['tenant_id' => $this->director->tenant_id]);
    $coach->assignedCategories()->attach($this->category->id);

    $tomorrow = now()->addDay()->format('Y-m-d');

    Livewire::actingAs($coach)
        ->test('pages::sessions.create')
        ->set('category_id', $this->category->id)
        ->set('session_date', $tomorrow)
        ->set('start_time', '10:00')
        ->set('duration_minutes', 60)
        ->call('save')
        ->assertHasNoErrors();

    expect(
        TrainingSession::where('category_id', $this->category->id)
            ->whereNull('schedule_id')
            ->exists()
    )->toBeTrue();
});

it('coach cannot access schedule management routes', function () {
    $coach = User::factory()->coach()->create(['tenant_id' => $this->director->tenant_id]);

    $this->actingAs($coach)->get(route('schedules.index'))->assertForbidden();
    $this->actingAs($coach)->get(route('schedules.create'))->assertForbidden();
});

it('schedules and sessions are scoped to tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherDirector = User::factory()->director()->create(['tenant_id' => $otherTenant->id]);
    $otherCategory = Category::factory()->create(['tenant_id' => $otherTenant->id]);

    $mySchedule = TrainingSchedule::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'day_of_week' => DayOfWeek::Wednesday->value,
        'start_time'  => '14:00',
        'duration_minutes' => 60,
    ]);

    $otherSchedule = TrainingSchedule::factory()->create([
        'tenant_id'   => $otherTenant->id,
        'category_id' => $otherCategory->id,
        'day_of_week' => DayOfWeek::Wednesday->value,
        'start_time'  => '14:00',
        'duration_minutes' => 60,
    ]);

    app()->instance(\App\Models\Tenant::class, $this->director->tenant);

    $visible = TrainingSchedule::all()->pluck('id');

    expect($visible)->toContain($mySchedule->id)
        ->and($visible)->not->toContain($otherSchedule->id);
});
