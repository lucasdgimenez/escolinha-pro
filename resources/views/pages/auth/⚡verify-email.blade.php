<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Verificar e-mail')] class extends Component
{
    public function mount(): void
    {
        if (auth()->user()->hasVerifiedEmail()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function resend(): void
    {
        auth()->user()->sendEmailVerificationNotification();

        session()->flash('resent', true);
    }
};
?>

<div>
    <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">Verifique seu e-mail</h2>

    <p class="text-sm text-gray-600 mb-4 text-center">
        Enviamos um link de verificação para
        <strong class="text-gray-800">{{ auth()->user()->email }}</strong>.
        Clique no link para ativar sua conta.
    </p>

    @session('resent')
        <div class="mb-4 rounded-lg bg-success-50 border border-success-200 p-3 text-sm text-success-700">
            Um novo link de verificação foi enviado para seu e-mail.
        </div>
    @endsession

    <div class="flex flex-col gap-3">
        <x-button wire:click="resend" variant="secondary" class="w-full">
            Reenviar e-mail de verificação
        </x-button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full text-sm text-gray-500 hover:text-gray-700 transition-colors text-center"
            >
                Sair da conta
            </button>
        </form>
    </div>
</div>
