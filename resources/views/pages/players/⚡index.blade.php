<?php

use App\Models\Player;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Atletas')] class extends Component
{
    #[Computed]
    public function players(): Collection
    {
        return Player::with('category')->orderBy('name')->get();
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Atletas</h2>
        <div class="flex gap-3">
            <a href="{{ route('players.import') }}" wire:navigate class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Importar CSV
            </a>
            <a href="{{ route('players.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                Novo atleta
            </a>
        </div>
    </div>

    @if ($this->players->isEmpty())
        <p class="text-gray-500 text-sm">Nenhum atleta cadastrado.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="pb-3 pr-4">Nome</th>
                        <th class="pb-3 pr-4">Categoria</th>
                        <th class="pb-3 pr-4">Posição</th>
                        <th class="pb-3 pr-4">Pé dominante</th>
                        <th class="pb-3 pr-4">Responsável</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->players as $player)
                        <tr wire:key="{{ $player->id }}">
                            <td class="py-3 pr-4 font-medium">{{ $player->name }}</td>
                            <td class="py-3 pr-4">{{ $player->category?->name ?? '—' }}</td>
                            <td class="py-3 pr-4">{{ $player->position->label() }}</td>
                            <td class="py-3 pr-4">{{ $player->dominant_foot->label() }}</td>
                            <td class="py-3 pr-4">{{ $player->guardian_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
