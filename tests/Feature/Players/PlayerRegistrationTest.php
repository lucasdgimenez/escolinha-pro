<?php

use App\Enums\DominantFoot;
use App\Enums\PlayerPosition;
use App\Models\Category;
use App\Models\Player;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\Players\PlayerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    Storage::fake('public');
});

it('players index page renders for director', function () {
    $director = User::factory()->director()->create();

    $this->actingAs($director)->get(route('players.index'))->assertOk();
});

it('player is created successfully via form', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    Livewire::actingAs($director)
        ->test('pages::players.create')
        ->set('form.name', 'João da Silva')
        ->set('form.date_of_birth', '2012-05-10')
        ->set('form.position', PlayerPosition::Forward->value)
        ->set('form.dominant_foot', DominantFoot::Right->value)
        ->set('form.guardian_name', 'Maria da Silva')
        ->set('form.guardian_email', 'maria@exemplo.com')
        ->set('form.guardian_phone', '(11) 99999-0000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('players.index'));

    expect(Player::withoutGlobalScopes()->where('guardian_email', 'maria@exemplo.com')->exists())->toBeTrue();
});

it('assigns correct category based on CBF date of birth', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    // TenantObserver auto-creates Sub-13 (min_age=12, max_age=13) for every new tenant
    $category = Category::withoutGlobalScopes()
        ->where('tenant_id', $director->tenant_id)
        ->where('name', 'Sub-13')
        ->firstOrFail();

    $birthYear = now()->year - 12;

    $player = app(PlayerService::class)->create([
        'name'           => 'Carlos Teste',
        'date_of_birth'  => "{$birthYear}-06-15",
        'position'       => PlayerPosition::Midfielder->value,
        'dominant_foot'  => DominantFoot::Left->value,
        'guardian_name'  => 'Roberto Teste',
        'guardian_email' => 'roberto@teste.com',
        'guardian_phone' => null,
    ], null, $director);

    expect($player->category_id)->toBe($category->id);
});

it('assigns null category when no match found for age', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    $player = app(PlayerService::class)->create([
        'name'           => 'Adulto Teste',
        'date_of_birth'  => '1990-01-01',
        'position'       => PlayerPosition::Goalkeeper->value,
        'dominant_foot'  => DominantFoot::Both->value,
        'guardian_name'  => 'Pai Teste',
        'guardian_email' => 'pai@teste.com',
        'guardian_phone' => null,
    ], null, $director);

    expect($player->category_id)->toBeNull();
});

it('sends parent invitation on player creation', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    app(PlayerService::class)->create([
        'name'           => 'Lucas Teste',
        'date_of_birth'  => '2013-03-01',
        'position'       => PlayerPosition::Defender->value,
        'dominant_foot'  => DominantFoot::Right->value,
        'guardian_name'  => 'Ana Teste',
        'guardian_email' => 'ana@teste.com',
        'guardian_phone' => null,
    ], null, $director);

    Notification::assertSentOnDemand(
        InvitationNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ana@teste.com'
    );
});

it('stores player photo to public disk', function () {
    $director = User::factory()->director()->create();
    Notification::fake();
    $photo = UploadedFile::fake()->image('foto.jpg');

    Livewire::actingAs($director)
        ->test('pages::players.create')
        ->set('form.name', 'Pedro Foto')
        ->set('form.date_of_birth', '2011-08-20')
        ->set('form.position', PlayerPosition::Forward->value)
        ->set('form.dominant_foot', DominantFoot::Right->value)
        ->set('form.guardian_name', 'Pai Foto')
        ->set('form.guardian_email', 'paifoto@teste.com')
        ->set('photo', $photo)
        ->call('save')
        ->assertHasNoErrors();

    $player = Player::withoutGlobalScopes()->where('guardian_email', 'paifoto@teste.com')->firstOrFail();
    expect($player->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($player->photo_path);
});

it('players are scoped to tenant', function () {
    $director1 = User::factory()->director()->create();
    $director2 = User::factory()->director()->create();
    Notification::fake();

    Player::factory()->create([
        'tenant_id'      => $director1->tenant_id,
        'guardian_email' => 'tenanta@teste.com',
    ]);

    $response = $this->actingAs($director2)->get(route('players.index'));
    $response->assertOk();
    $response->assertDontSee('tenanta@teste.com');
});

it('coach cannot access players index page', function () {
    $director = User::factory()->director()->create();
    $coach = User::factory()->coach()->create(['tenant_id' => $director->tenant_id]);

    $this->actingAs($coach)->get(route('players.index'))->assertForbidden();
});

it('validates required fields on player create form', function () {
    $director = User::factory()->director()->create();

    Livewire::actingAs($director)
        ->test('pages::players.create')
        ->call('save')
        ->assertHasErrors([
            'form.name',
            'form.date_of_birth',
            'form.position',
            'form.dominant_foot',
            'form.guardian_name',
            'form.guardian_email',
        ]);
});

it('creates player silently when guardian email is already registered', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    User::factory()->guardian()->create(['email' => 'existente@teste.com']);

    $player = app(PlayerService::class)->create([
        'name'           => 'Atleta Existente',
        'date_of_birth'  => '2014-01-01',
        'position'       => PlayerPosition::Midfielder->value,
        'dominant_foot'  => DominantFoot::Left->value,
        'guardian_name'  => 'Guardião Existente',
        'guardian_email' => 'existente@teste.com',
        'guardian_phone' => null,
    ], null, $director);

    expect($player)->not->toBeNull();
    expect(Player::withoutGlobalScopes()->where('guardian_email', 'existente@teste.com')->exists())->toBeTrue();
});
