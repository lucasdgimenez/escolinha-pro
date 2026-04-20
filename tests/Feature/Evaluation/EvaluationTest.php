<?php

use App\Enums\NarrativeStatus;
use App\Jobs\GenerateEvaluationNarrative;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\EvaluationMetric;
use App\Models\EvaluationMetricKey;
use App\Models\EvaluationNarrative;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->seed(\Database\Seeders\EvaluationMetricKeysSeeder::class);

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

    $this->metricKeys = EvaluationMetricKey::all();
    $this->scores = $this->metricKeys->pluck('id')->mapWithKeys(fn ($id) => [$id => '7'])->all();
});

it('coach can create evaluation with all metric scores', function () {
    Queue::fake();

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.create')
        ->set('player_id', (string) $this->player->id)
        ->set('evaluated_at', now()->format('Y-m-d'))
        ->set('scores', $this->scores)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('evaluations.show', Evaluation::first()));

    expect(Evaluation::where('player_id', $this->player->id)->exists())->toBeTrue();
});

it('each metric score is persisted as separate evaluation_metric row', function () {
    Queue::fake();

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.create')
        ->set('player_id', (string) $this->player->id)
        ->set('evaluated_at', now()->format('Y-m-d'))
        ->set('scores', $this->scores)
        ->call('save');

    $evaluation = Evaluation::where('player_id', $this->player->id)->first();

    expect(EvaluationMetric::where('evaluation_id', $evaluation->id)->count())
        ->toBe($this->metricKeys->count());
});

it('duplicate score for same evaluation and metric key is not allowed', function () {
    Queue::fake();

    $evaluation = Evaluation::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'player_id'   => $this->player->id,
        'coach_id'    => $this->coach->id,
        'category_id' => $this->category->id,
    ]);

    $metricKey = $this->metricKeys->first();

    EvaluationMetric::create([
        'evaluation_id' => $evaluation->id,
        'metric_key_id' => $metricKey->id,
        'score'         => 7,
    ]);

    expect(fn () => EvaluationMetric::create([
        'evaluation_id' => $evaluation->id,
        'metric_key_id' => $metricKey->id,
        'score'         => 8,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('narrative generation job is dispatched after evaluation save', function () {
    Queue::fake();

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.create')
        ->set('player_id', (string) $this->player->id)
        ->set('evaluated_at', now()->format('Y-m-d'))
        ->set('scores', $this->scores)
        ->call('save');

    Queue::assertPushed(GenerateEvaluationNarrative::class);
});

it('evaluation is saved even when ai job fails', function () {
    Queue::fake([GenerateEvaluationNarrative::class]);

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.create')
        ->set('player_id', (string) $this->player->id)
        ->set('evaluated_at', now()->format('Y-m-d'))
        ->set('scores', $this->scores)
        ->call('save');

    expect(Evaluation::where('player_id', $this->player->id)->exists())->toBeTrue();
});

it('coach can edit narrative text', function () {
    Queue::fake();

    $evaluation = Evaluation::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'player_id'   => $this->player->id,
        'coach_id'    => $this->coach->id,
        'category_id' => $this->category->id,
    ]);

    EvaluationNarrative::create([
        'evaluation_id'     => $evaluation->id,
        'status'            => NarrativeStatus::Generated,
        'ai_generated_text' => 'Original AI text.',
        'generated_at'      => now(),
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.show', ['evaluation' => $evaluation])
        ->set('editedText', 'Custom edited narrative.')
        ->call('saveNarrative')
        ->assertHasNoErrors();

    expect($evaluation->narrative->fresh()->edited_text)->toBe('Custom edited narrative.');
});

it('coach can regenerate narrative', function () {
    Queue::fake();

    $evaluation = Evaluation::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'player_id'   => $this->player->id,
        'coach_id'    => $this->coach->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.show', ['evaluation' => $evaluation])
        ->call('generateNarrative');

    Queue::assertPushed(GenerateEvaluationNarrative::class);
});

it('coach cannot access evaluation for player outside assigned category', function () {
    $otherCategory = Category::factory()->create(['tenant_id' => $this->director->tenant_id]);
    $otherPlayer = Player::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'category_id' => $otherCategory->id,
    ]);

    $evaluation = Evaluation::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'player_id'   => $otherPlayer->id,
        'coach_id'    => $this->coach->id,
        'category_id' => $otherCategory->id,
    ]);

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.show', ['evaluation' => $evaluation])
        ->assertStatus(403);
});

it('director can view all evaluations within tenant', function () {
    Queue::fake();

    $evaluation = Evaluation::factory()->create([
        'tenant_id'   => $this->director->tenant_id,
        'player_id'   => $this->player->id,
        'coach_id'    => $this->coach->id,
        'category_id' => $this->category->id,
    ]);

    Livewire::actingAs($this->director)
        ->test('pages::evaluations.show', ['evaluation' => $evaluation])
        ->assertStatus(200);
});

it('score validation rejects values outside 1 to 10 range', function () {
    $invalidScores = $this->scores;
    $firstKey = array_key_first($invalidScores);
    $invalidScores[$firstKey] = '11';

    Livewire::actingAs($this->coach)
        ->test('pages::evaluations.create')
        ->set('player_id', (string) $this->player->id)
        ->set('evaluated_at', now()->format('Y-m-d'))
        ->set('scores', $invalidScores)
        ->call('save')
        ->assertHasErrors(["scores.{$firstKey}"]);
});
