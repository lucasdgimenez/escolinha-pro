<?php

use App\Enums\DominantFoot;
use App\Enums\PlayerPosition;
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

it('CSV import creates all valid players and sends parent invites', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    $rows = [
        [
            'name'           => 'Atleta Um',
            'date_of_birth'  => '2012-01-01',
            'position'       => PlayerPosition::Forward->value,
            'dominant_foot'  => DominantFoot::Right->value,
            'guardian_name'  => 'Pai Um',
            'guardian_email' => 'um@teste.com',
            'guardian_phone' => '',
        ],
        [
            'name'           => 'Atleta Dois',
            'date_of_birth'  => '2013-03-15',
            'position'       => PlayerPosition::Goalkeeper->value,
            'dominant_foot'  => DominantFoot::Left->value,
            'guardian_name'  => 'Pai Dois',
            'guardian_email' => 'dois@teste.com',
            'guardian_phone' => '(11) 98888-0000',
        ],
    ];

    $result = app(PlayerService::class)->importFromCsv($rows, $director);

    expect($result['created'])->toBe(2);
    expect($result['errors'])->toBeEmpty();
    expect(Player::withoutGlobalScopes()->where('guardian_email', 'um@teste.com')->exists())->toBeTrue();
    expect(Player::withoutGlobalScopes()->where('guardian_email', 'dois@teste.com')->exists())->toBeTrue();
    Notification::assertSentOnDemand(InvitationNotification::class);
});

it('CSV import with invalid rows returns partial success and error list', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    $rows = [
        [
            'name'           => 'Atleta Válido',
            'date_of_birth'  => '2012-05-10',
            'position'       => PlayerPosition::Midfielder->value,
            'dominant_foot'  => DominantFoot::Right->value,
            'guardian_name'  => 'Pai Válido',
            'guardian_email' => 'valido@teste.com',
            'guardian_phone' => '',
        ],
        [
            'name'           => 'Sem Email',
            'date_of_birth'  => '2013-01-01',
            'position'       => PlayerPosition::Defender->value,
            'dominant_foot'  => DominantFoot::Both->value,
            'guardian_name'  => 'Pai Sem Email',
            'guardian_email' => '',
            'guardian_phone' => '',
        ],
    ];

    $result = app(PlayerService::class)->importFromCsv($rows, $director);

    expect($result['created'])->toBe(1);
    expect($result['errors'])->toHaveCount(1);
    expect($result['errors'][0]['row'])->toBe(3);
});

it('CSV import silently skips invitation when guardian email is already registered', function () {
    $director = User::factory()->director()->create();
    Notification::fake();

    User::factory()->guardian()->create(['email' => 'jaexiste@teste.com']);

    $rows = [
        [
            'name'           => 'Atleta Guardião Existente',
            'date_of_birth'  => '2014-02-20',
            'position'       => PlayerPosition::Forward->value,
            'dominant_foot'  => DominantFoot::Left->value,
            'guardian_name'  => 'Guardião Já Cadastrado',
            'guardian_email' => 'jaexiste@teste.com',
            'guardian_phone' => '',
        ],
    ];

    $result = app(PlayerService::class)->importFromCsv($rows, $director);

    expect($result['created'])->toBe(1);
    expect($result['errors'])->toBeEmpty();
    expect(Player::withoutGlobalScopes()->where('guardian_email', 'jaexiste@teste.com')->exists())->toBeTrue();
});

it('CSV template download returns correct column headers', function () {
    $director = User::factory()->director()->create();

    $response = Livewire::actingAs($director)
        ->test('pages::players.import')
        ->call('downloadTemplate');

    $downloadResponse = $response->instance()->downloadTemplate();

    ob_start();
    $downloadResponse->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('name');
    expect($content)->toContain('date_of_birth');
    expect($content)->toContain('position');
    expect($content)->toContain('dominant_foot');
    expect($content)->toContain('guardian_name');
    expect($content)->toContain('guardian_email');
    expect($content)->toContain('guardian_phone');
});

it('CSV import form rejects non-CSV files', function () {
    $director = User::factory()->director()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::actingAs($director)
        ->test('pages::players.import')
        ->set('csvFile', $file)
        ->call('import')
        ->assertHasErrors(['csvFile']);
});
