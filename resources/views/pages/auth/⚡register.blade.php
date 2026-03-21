<?php

use App\Livewire\Forms\RegisterForm;
use App\Services\Auth\RegistrationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Criar conta')] class extends Component
{
    public RegisterForm $form;

    public function register(RegistrationService $registrationService): void
    {
        $this->form->validate();

        $user = $registrationService->register(
            name: $this->form->name,
            email: $this->form->email,
            password: $this->form->password,
            academyName: $this->form->academy_name,
        );

        auth()->login($user);

        $user->sendEmailVerificationNotification();

        $this->redirect(route('verification.notice'), navigate: true);
    }
};
?>

<div>
    <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Crie sua conta</h2>

    <form wire:submit="register" class="flex flex-col gap-4">
        <x-input
            name="form.name"
            label="Nome completo"
            wire:model="form.name"
            required
        />

        <x-input
            name="form.email"
            label="E-mail"
            type="email"
            wire:model="form.email"
            required
        />

        <x-input
            name="form.academy_name"
            label="Nome da academia"
            wire:model="form.academy_name"
            required
        />

        <x-input
            name="form.password"
            label="Senha"
            type="password"
            wire:model="form.password"
            required
        />

        <x-input
            name="form.password_confirmation"
            label="Confirmar senha"
            type="password"
            wire:model="form.password_confirmation"
            required
        />

        <x-button type="submit" class="w-full mt-2">
            Criar conta
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Já tem uma conta?
        <a href="{{ route('login') }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium">
            Entrar
        </a>
    </p>
</div>
