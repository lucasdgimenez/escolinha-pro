<?php

use App\Enums\AttendanceStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\Player;
use App\Models\SessionAttendance;
use App\Models\Tenant;
use App\Models\TrainingSession;
use App\Models\User;
use App\Notifications\PlayerAbsentNotification;
use Illuminate\Support\Facades\Notification;
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

    $this->player = Player::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
    ]);

    $this->session = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::InProgress->value,
    ]);
});

it('marks player as present', function () {
    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->call('markAttendance', $this->player->id, AttendanceStatus::Present->value)
        ->assertHasNoErrors();

    expect(SessionAttendance::where([
        'training_session_id' => $this->session->id,
        'player_id'           => $this->player->id,
        'status'              => AttendanceStatus::Present->value,
    ])->exists())->toBeTrue();
});

it('marks player as absent', function () {
    Notification::fake();

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->call('markAttendance', $this->player->id, AttendanceStatus::Absent->value)
        ->assertHasNoErrors();

    expect(SessionAttendance::where([
        'training_session_id' => $this->session->id,
        'player_id'           => $this->player->id,
        'status'              => AttendanceStatus::Absent->value,
    ])->exists())->toBeTrue();
});

it('marks player as justified', function () {
    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->call('markAttendance', $this->player->id, AttendanceStatus::Justified->value)
        ->assertHasNoErrors();

    expect(SessionAttendance::where([
        'training_session_id' => $this->session->id,
        'player_id'           => $this->player->id,
        'status'              => AttendanceStatus::Justified->value,
    ])->exists())->toBeTrue();
});

it('updating attendance does not create duplicate record', function () {
    Notification::fake();

    $component = Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session]);

    $component->call('markAttendance', $this->player->id, AttendanceStatus::Present->value);
    $component->call('markAttendance', $this->player->id, AttendanceStatus::Absent->value);

    expect(SessionAttendance::where([
        'training_session_id' => $this->session->id,
        'player_id'           => $this->player->id,
    ])->count())->toBe(1)
        ->and(SessionAttendance::where([
            'training_session_id' => $this->session->id,
            'player_id'           => $this->player->id,
        ])->first()->status)->toBe(AttendanceStatus::Absent);
});

it('absent notification sent to parent when player marked absent', function () {
    Notification::fake();

    $parent = User::factory()->guardian()->create([
        'tenant_id' => $this->director->tenant_id,
        'email'     => $this->player->guardian_email,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->call('markAttendance', $this->player->id, AttendanceStatus::Absent->value);

    Notification::assertSentTo($parent, PlayerAbsentNotification::class);
});

it('no notification if parent user not found', function () {
    Notification::fake();

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $this->session])
        ->call('markAttendance', $this->player->id, AttendanceStatus::Absent->value);

    Notification::assertNothingSent();
});

it('cannot mark attendance on a scheduled session', function () {
    $scheduledSession = TrainingSession::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $this->category->id,
        'status'      => SessionStatus::Scheduled->value,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::sessions.show', ['session' => $scheduledSession])
        ->call('markAttendance', $this->player->id, AttendanceStatus::Present->value);

    expect(SessionAttendance::where([
        'training_session_id' => $scheduledSession->id,
        'player_id'           => $this->player->id,
    ])->exists())->toBeFalse();
});
