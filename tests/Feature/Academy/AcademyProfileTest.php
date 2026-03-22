<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    Storage::fake('public');
});

it('academy profile page renders for director', function () {
    $director = User::factory()->director()->create();

    $this->actingAs($director)->get(route('academy.profile'))->assertOk();
});

it('director can update academy profile fields', function () {
    $director = User::factory()->director()->create();

    Livewire::actingAs($director)
        ->test('pages::academy.profile')
        ->set('form.name', 'Academia Atualizada')
        ->set('form.city', 'São Paulo')
        ->set('form.state', 'SP')
        ->set('form.phone', '(11) 99999-9999')
        ->call('save')
        ->assertHasNoErrors();

    expect($director->tenant->fresh()->name)->toBe('Academia Atualizada');
    expect($director->tenant->fresh()->city)->toBe('São Paulo');
});

it('director can upload a logo', function () {
    $director = User::factory()->director()->create();
    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    Livewire::actingAs($director)
        ->test('pages::academy.profile')
        ->set('logo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $tenant = $director->tenant->fresh();
    expect($tenant->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($tenant->logo_path);
});

it('validates required name field', function () {
    $director = User::factory()->director()->create();

    Livewire::actingAs($director)
        ->test('pages::academy.profile')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

it('coach cannot access academy profile page', function () {
    $director = User::factory()->director()->create();
    $coach = User::factory()->coach()->create(['tenant_id' => $director->tenant_id]);

    $this->actingAs($coach)->get(route('academy.profile'))->assertForbidden();
});
