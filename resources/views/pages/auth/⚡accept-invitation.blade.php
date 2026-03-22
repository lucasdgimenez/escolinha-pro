<?php

use App\Enums\RoleSlug;
use App\Livewire\Forms\AcceptInvitationForm;
use App\Models\Invitation;
use App\Services\Auth\InvitationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Aceitar convite')] class extends Component
{
    public AcceptInvitationForm $form;
    public ?Invitation $invitation = null;
    public string $errorMessage = '';

    public function mount(string $token): void
    {
        $invitation = Invitation::withoutGlobalScopes()->where('token', $token)->first();

        if (! $invitation) {
            $this->errorMessage = 'Convite não encontrado.';
            return;
        }

        if ($invitation->isAccepted()) {
            $this->errorMessage = 'Este convite já foi utilizado.';
            return;
        }

        if ($invitation->isExpired()) {
            $this->errorMessage = 'Este convite expirou. Solicite um novo convite.';
            return;
        }

        $this->invitation = $invitation;
    }

    public function accept(InvitationService $service): void
    {
        $this->form->validate();

        $user = $service->accept($this->invitation, $this->form->name, $this->form->password);

        auth()->login($user);

        $route = match ($user->role->slug) {
            RoleSlug::AcademyDirector, RoleSlug::Coach => 'dashboard',
            default => 'portal',
        };

        $this->redirect(route($route), navigate: true);
    }
};
?>

<div>
    @if ($errorMessage)
        <div class="text-center">
            <p class="text-red-600 font-medium mb-4">{{ $errorMessage }}</p>
            <a href="{{ route('login') }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                Ir para o login
            </a>
        </div>
    @else
        <h2 class="text-xl font-semibold text-gray-800 mb-2 text-center">Aceitar convite</h2>
        <p class="text-sm text-gray-600 text-center mb-6">
            Você foi convidado como <strong>{{ $invitation->role->name }}</strong>.<br>
            Crie sua senha para acessar a plataforma.
        </p>

        <form wire:submit="accept" class="flex flex-col gap-4">
            <x-input
                name="email"
                label="E-mail"
                type="email"
                value="{{ $invitation->email }}"
                readonly
                disabled
            />

            <x-input
                name="form.name"
                label="Nome completo"
                wire:model="form.name"
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
                Criar conta e entrar
            </x-button>
        </form>
    @endif
</div>
