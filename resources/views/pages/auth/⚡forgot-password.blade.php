<?php

use App\Livewire\Forms\ForgotPasswordForm;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Esqueci a senha')] class extends Component
{
    public ForgotPasswordForm $form;

    public function sendResetLink(): void
    {
        $this->form->validate();

        Password::sendResetLink(['email' => $this->form->email]);

        session()->flash('status', 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.');

        $this->form->reset();
    }
};
?>

<div>
    <h2 class="text-xl font-semibold text-gray-800 mb-2 text-center">Esqueceu a senha?</h2>
    <p class="text-sm text-gray-600 mb-6 text-center">
        Informe seu e-mail e enviaremos um link para redefinição de senha.
    </p>

    @session('status')
        <div class="mb-4 rounded-lg bg-success-50 border border-success-200 p-3 text-sm text-success-700">
            {{ $value }}
        </div>
    @endsession

    <form wire:submit="sendResetLink" class="flex flex-col gap-4">
        <x-input
            name="form.email"
            label="E-mail"
            type="email"
            wire:model="form.email"
            required
        />

        <x-button type="submit" class="w-full mt-2">
            Enviar link de redefinição
        </x-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Lembrou a senha?
        <a href="{{ route('login') }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium">
            Entrar
        </a>
    </p>
</div>
