<?php

use App\Enums\DominantFoot;
use App\Enums\Position;
use App\Enums\RoleSlug;
use App\Models\Category;
use App\Models\Player;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\Player\PlayerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

    $this->tenant = Tenant::factory()->create();
    app()->instance(Tenant::class, $this->tenant);

    $this->director = User::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'role_id' => Role::where('slug', RoleSlug::AcademyDirector->value)->value('id'),
        'name' => 'Diretor',
        'email' => 'diretor@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);
});

it('creates a player and auto-assigns to the correct category based on DOB', function () {
    $service = app(PlayerService::class);

    // Born to be Sub-11 (age = 10 or 11 in the current calendar year)
    $birthYear = now()->year - 11;

    $player = $service->create([
        'name' => 'João Silva',
        'date_of_birth' => "{$birthYear}-06-15",
        'position' => Position::Midfielder->value,
        'dominant_foot' => DominantFoot::Right->value,
        'guardian_name' => 'Maria Silva',
        'guardian_email' => 'maria@example.com',
        'guardian_phone' => '11999990000',
    ], null, $this->director);

    $player->refresh();

    $sub11 = Category::where('name', 'Sub-11')->first();
    expect($sub11)->not->toBeNull();
    expect($player->category_id)->toBe($sub11->id);
});

it('creates a player without assigning category if no matching age bracket exists', function () {
    $service = app(PlayerService::class);

    // Age 20 — outside all default CBF brackets
    $birthYear = now()->year - 20;

    $player = $service->create([
        'name' => 'Carlos Adulto',
        'date_of_birth' => "{$birthYear}-01-01",
        'position' => Position::Goalkeeper->value,
        'dominant_foot' => DominantFoot::Left->value,
        'guardian_name' => 'Pedro Adulto',
        'guardian_email' => 'pedro@example.com',
        'guardian_phone' => null,
    ], null, $this->director);

    expect($player->category_id)->toBeNull();
});

it('triggers a parent invitation on player creation', function () {
    Notification::fake();

    $service = app(PlayerService::class);

    $service->create([
        'name' => 'Ana Souza',
        'date_of_birth' => now()->subYears(10)->format('Y-m-d'),
        'position' => Position::Forward->value,
        'dominant_foot' => DominantFoot::Both->value,
        'guardian_name' => 'Rosa Souza',
        'guardian_email' => 'rosa@example.com',
        'guardian_phone' => null,
    ], null, $this->director);

    Notification::assertSentOnDemand(
        InvitationNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'rosa@example.com'
    );
});

it('saves the player photo and stores the path', function () {
    Storage::fake('public');

    $service = app(PlayerService::class);

    $photo = UploadedFile::fake()->image('foto.jpg');

    $player = $service->create([
        'name' => 'Lucas Foto',
        'date_of_birth' => now()->subYears(12)->format('Y-m-d'),
        'position' => Position::Defender->value,
        'dominant_foot' => DominantFoot::Right->value,
        'guardian_name' => 'Pai Lucas',
        'guardian_email' => 'pai@example.com',
        'guardian_phone' => null,
    ], $photo, $this->director);

    expect($player->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($player->photo_path);
});

it('stores players correctly in the database', function () {
    $service = app(PlayerService::class);

    $service->create([
        'name' => 'Bruno Teste',
        'date_of_birth' => now()->subYears(9)->format('Y-m-d'),
        'position' => Position::RightBack->value,
        'dominant_foot' => DominantFoot::Right->value,
        'guardian_name' => 'Pai Bruno',
        'guardian_email' => 'pai.bruno@example.com',
        'guardian_phone' => '11988880000',
    ], null, $this->director);

    $player = Player::where('guardian_email', 'pai.bruno@example.com')->first();

    expect($player)->not->toBeNull();
    expect($player->name)->toBe('Bruno Teste');
    expect($player->position)->toBe(Position::RightBack);
    expect($player->dominant_foot)->toBe(DominantFoot::Right);
    expect($player->tenant_id)->toBe($this->tenant->id);
});

it('imports players from a valid CSV file', function () {
    Notification::fake();

    $service = app(PlayerService::class);

    $birthYear = now()->year - 10;
    $csv = implode("\n", [
        'Nome,Data de Nascimento,Posição,Pé Dominante,Nome do Responsável,Email do Responsável,Telefone do Responsável',
        "Atleta Um,{$birthYear}-01-01,meia,direito,Pai Um,pai.um@example.com,11911110001",
        "Atleta Dois,{$birthYear}-02-02,goleiro,esquerdo,Mãe Dois,mae.dois@example.com,11922220002",
        "Atleta Três,{$birthYear}-03-03,zagueiro,ambidestro,Pai Três,pai.tres@example.com,",
    ]);

    $file = UploadedFile::fake()->createWithContent('atletas.csv', $csv);

    $result = $service->importFromCsv($file, $this->director);

    expect($result['imported'])->toBe(3);
    expect($result['errors'])->toBeEmpty();
    expect(Player::count())->toBe(3);
});

it('reports errors for invalid CSV rows and imports the valid ones', function () {
    Notification::fake();

    $service = app(PlayerService::class);

    $birthYear = now()->year - 10;
    $csv = implode("\n", [
        'Nome,Data de Nascimento,Posição,Pé Dominante,Nome do Responsável,Email do Responsável,Telefone do Responsável',
        "Atleta Válido,{$birthYear}-01-01,meia,direito,Pai Válido,pai.valido@example.com,",
        "Sem Email,{$birthYear}-02-02,goleiro,esquerdo,Pai Sem Email,,",
        "Outro Válido,{$birthYear}-03-03,zagueiro,ambidestro,Pai Outro,pai.outro@example.com,",
    ]);

    $file = UploadedFile::fake()->createWithContent('atletas.csv', $csv);

    $result = $service->importFromCsv($file, $this->director);

    expect($result['imported'])->toBe(2);
    expect($result['errors'])->toHaveCount(1);
    expect($result['errors'][0]['row'])->toBe(3);
    expect(Player::count())->toBe(2);
});

it('academy director can access the players index route', function () {
    $this->actingAs($this->director)
        ->get(route('players.index'))
        ->assertOk();
});

it('academy director can access the players create route', function () {
    $this->actingAs($this->director)
        ->get(route('players.create'))
        ->assertOk();
});

it('coach can access the players create route', function () {
    $coach = User::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'role_id' => Role::where('slug', RoleSlug::Coach->value)->value('id'),
        'name' => 'Treinador',
        'email' => 'treinador@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $this->actingAs($coach)
        ->get(route('players.create'))
        ->assertOk();
});

it('parent cannot access player management routes', function () {
    $parent = User::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'role_id' => Role::where('slug', RoleSlug::Parent->value)->value('id'),
        'name' => 'Responsável',
        'email' => 'responsavel@example.com',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $this->actingAs($parent)
        ->get(route('players.index'))
        ->assertForbidden();
});
