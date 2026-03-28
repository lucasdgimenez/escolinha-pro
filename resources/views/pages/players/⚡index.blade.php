<?php

use App\Enums\RoleSlug;
use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Atletas')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function players(): LengthAwarePaginator
    {
        return Player::with('category')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when(
                auth()->user()->hasRole(RoleSlug::Coach),
                fn ($q) => $q->whereIn('category_id', auth()->user()->assignedCategories->pluck('id'))
            )
            ->orderBy('name')
            ->paginate(20);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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

    <div class="mb-4">
        <x-input
            name="search"
            label=""
            wire:model.live="search"
            placeholder="Buscar por nome..."
        />
    </div>

    @if ($this->players->isEmpty())
        <p class="text-gray-500 text-sm">Nenhum atleta cadastrado ainda.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="pb-3 pr-4">Foto</th>
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
                            <td class="py-3 pr-4">
                                @if ($player->photo_path)
                                    <img
                                        src="{{ Storage::disk('public')->url($player->photo_path) }}"
                                        alt="{{ $player->name }}"
                                        class="h-9 w-9 rounded-full object-cover"
                                    />
                                @else
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-gray-500 text-xs font-medium">
                                        {{ mb_substr($player->name, 0, 2) }}
                                    </span>
                                @endif
                            </td>
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

        <div class="mt-4">
            {{ $this->players->links() }}
        </div>
    @endif
</div>
