<?php

use App\Models\User;

it('dashboard route returns 200', function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    $user = User::factory()->director()->create();
    $this->actingAs($user)->get('/dashboard')->assertOk();
});

it('dashboard route renders the dashboard livewire component', function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    $user = User::factory()->director()->create();
    $this->actingAs($user)->get('/dashboard')->assertSee('Bem-vindo ao Escolinha Pro');
});

it('dashboard view contains app name from layout', function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    $user = User::factory()->director()->create();
    $this->actingAs($user)->get('/dashboard')->assertSee(config('app.name'));
});

it('button component renders with primary variant by default', function () {
    $this->blade('<x-button>Salvar</x-button>')
        ->assertSee('Salvar')
        ->assertSee('bg-primary-600');
});

it('button component renders with danger variant', function () {
    $this->blade('<x-button variant="danger">Excluir</x-button>')
        ->assertSee('Excluir')
        ->assertSee('bg-error-500');
});

it('button component renders with secondary variant', function () {
    $this->blade('<x-button variant="secondary">Cancelar</x-button>')
        ->assertSee('Cancelar')
        ->assertSee('bg-white');
});

it('input component renders label and name attribute', function () {
    $this->blade('<x-input name="email" label="E-mail" />')
        ->assertSee('E-mail')
        ->assertSee('name="email"', false);
});

it('input component shows error message when field has errors', function () {
    $this->withViewErrors(['email' => 'O e-mail é obrigatório.'])
        ->blade('<x-input name="email" label="E-mail" />')
        ->assertSee('O e-mail é obrigatório.')
        ->assertSee('border-error-500');
});

it('select component renders label and option text', function () {
    $this->blade('<x-select name="role" label="Função"><option value="coach">Treinador</option></x-select>')
        ->assertSee('Função')
        ->assertSee('Treinador');
});

it('checkbox component renders label and checkbox input', function () {
    $this->blade('<x-checkbox name="terms" label="Aceito os termos" />')
        ->assertSee('Aceito os termos')
        ->assertSee('type="checkbox"', false);
});

it('modal component renders title and body content', function () {
    $this->blade('<x-modal title="Confirmar exclusão">Tem certeza?</x-modal>')
        ->assertSee('Confirmar exclusão')
        ->assertSee('Tem certeza?');
});
