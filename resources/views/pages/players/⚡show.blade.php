<?php

use App\Enums\RoleSlug;
use App\Models\Evaluation;
use App\Models\Player;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Perfil do Atleta')] class extends Component
{
    public Player $player;

    public function mount(Player $player): void
    {
        if (auth()->user()->hasRole(RoleSlug::Coach)) {
            $assignedIds = auth()->user()->assignedCategories->pluck('id');

            if (! $assignedIds->contains($player->category_id)) {
                abort(403);
            }
        }

        $this->player = $player;
    }

    #[Computed]
    public function evaluations(): Collection
    {
        return $this->player->evaluations()
            ->with('coach')
            ->orderBy('evaluated_at', 'desc')
            ->get();
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('players.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">
                ← Voltar para atletas
            </a>
            <h2 class="text-xl font-semibold text-gray-800">{{ $this->player->name }}</h2>
        </div>
        <a href="{{ route('evaluations.create', ['player' => $this->player->id]) }}" wire:navigate
           class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
            Nova avaliação
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Player info card --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Informações</h3>

            <dl class="flex flex-col gap-3">
                <div>
                    <dt class="text-xs text-gray-500">Categoria</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->player->category->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Posição</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->player->position->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Pé dominante</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->player->dominant_foot->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Data de nascimento</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->player->date_of_birth->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Responsável</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->player->guardian_name }}</dd>
                    <dd class="text-xs text-gray-500">{{ $this->player->guardian_email }}</dd>
                </div>
            </dl>
        </div>

        {{-- Evaluations list --}}
        <div class="lg:col-span-2">
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">
                    Avaliações
                    <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        {{ $this->evaluations->count() }}
                    </span>
                </h3>

                @if ($this->evaluations->isEmpty())
                    <p class="text-sm text-gray-400">Nenhuma avaliação registrada.</p>
                @else
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                            <tr>
                                <th class="pb-3 pr-4">Data</th>
                                <th class="pb-3 pr-4">Coach</th>
                                <th class="pb-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($this->evaluations as $evaluation)
                                <tr wire:key="{{ $evaluation->id }}">
                                    <td class="py-3 pr-4 font-medium">{{ $evaluation->evaluated_at->format('d/m/Y') }}</td>
                                    <td class="py-3 pr-4 text-gray-600">{{ $evaluation->coach->name }}</td>
                                    <td class="py-3">
                                        <a href="{{ route('evaluations.show', $evaluation) }}" wire:navigate
                                           class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                                            Ver detalhes
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
