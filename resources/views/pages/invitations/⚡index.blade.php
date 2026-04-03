<?php

use App\Enums\RoleSlug;
use App\Livewire\Forms\InviteForm;
use App\Models\Invitation;
use App\Services\Auth\InvitationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Convites')] class extends Component
{
    public InviteForm $form;

    #[Computed]
    public function invitations(): Collection
    {
        return Invitation::with(['role', 'invitedBy'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function invite(InvitationService $service): void
    {
        $this->form->validate();

        try {
            $service->invite($this->form->email, RoleSlug::Coach, auth()->user());
            $this->form->reset();
            session()->flash('success', 'Convite enviado com sucesso.');
        } catch (\InvalidArgumentException $e) {
            $this->addError('form.email', $e->getMessage());
        }
    }

    public function resend(int $id, InvitationService $service): void
    {
        $invitation = Invitation::findOrFail($id);
        $service->resend($invitation);
        session()->flash('success', 'Convite reenviado.');
    }
};
?>

<div>
    @session('success')
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ $value }}
        </div>
    @endsession

    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Convidar treinador</h2>

        <form wire:submit="invite" class="flex gap-3 items-start">
            <div class="flex-1">
                <x-input
                    name="form.email"
                    label="E-mail do treinador"
                    type="email"
                    wire:model="form.email"
                    required
                />
            </div>
            <div class="pt-6">
                <x-button type="submit">
                    Enviar convite
                </x-button>
            </div>
        </form>
    </div>

    <h2 class="text-xl font-semibold text-gray-800 mb-4">Convites enviados</h2>

    @if ($this->invitations->isEmpty())
        <p class="text-gray-500 text-sm">Nenhum convite enviado ainda.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="pb-3 pr-4">E-mail</th>
                        <th class="pb-3 pr-4">Papel</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 pr-4">Enviado em</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->invitations as $invitation)
                        <tr wire:key="{{ $invitation->id }}">
                            <td class="py-3 pr-4">{{ $invitation->email }}</td>
                            <td class="py-3 pr-4">{{ $invitation->role->name }}</td>
                            <td class="py-3 pr-4">
                                @if ($invitation->isAccepted())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                        Aceito
                                    </span>
                                @elseif ($invitation->isExpired())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                        Expirado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">
                                        Pendente
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 pr-4">{{ $invitation->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3">
                                @if (! $invitation->isAccepted())
                                    <button
                                        wire:click="resend('{{ $invitation->id }}')"
                                        wire:confirm="Reenviar o convite para {{ $invitation->email }}?"
                                        class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                                    >
                                        Reenviar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
