<?php

use App\Enums\RoleSlug;
use App\Livewire\Forms\LoginForm;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Entrar')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->form->validate();

        if (! auth()->attempt([
            'email'    => $this->form->email,
            'password' => $this->form->password,
        ], $this->form->remember)) {
            $this->addError('form.email', 'As credenciais informadas não estão corretas.');

            return;
        }

        session()->regenerate();

        $user = auth()->user();

        $route = match ($user->role->slug) {
            RoleSlug::SuperAdmin,
            RoleSlug::AcademyDirector,
            RoleSlug::Coach => 'dashboard',
            default         => 'portal',
        };

        $this->redirect(route($route), navigate: true);
    }
};
?>

<div>
    <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Entrar na sua conta</h2>

    <form wire:submit="login" class="flex flex-col gap-4">
        <x-input
            name="form.email"
            label="E-mail"
            type="email"
            wire:model="form.email"
            required
        />

        <x-input
            name="form.password"
            label="Senha"
            type="password"
            wire:model="form.password"
            required
        />

        <x-checkbox
            name="form.remember"
            label="Lembrar de mim"
            wire:model="form.remember"
        />

        <x-button type="submit" class="w-full mt-2">
            Entrar
        </x-button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-600 flex flex-col gap-2">
        <a href="{{ route('password.request') }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium">
            Esqueceu a senha?
        </a>
        <span>
            Não tem uma conta?
            <a href="{{ route('register') }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium">
                Criar conta
            </a>
        </span>
    </div>
</div>
