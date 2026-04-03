<?php

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Academy\CoachAssignmentService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Atribuições de Treinadores')] class extends Component
{
    public ?string $editingCoachId = null;

    public array $selectedCategoryIds = [];

    #[Computed]
    public function coaches(): Collection
    {
        $tenant = auth()->user()->tenant ?? app(Tenant::class);

        return app(CoachAssignmentService::class)->getCoachesWithCategories($tenant);
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::where('is_active', true)->orderBy('min_age')->get();
    }

    public function openEdit(int $coachId): void
    {
        $coach = User::withoutGlobalScopes()->findOrFail($coachId);
        $this->editingCoachId = $coach->id;
        $this->selectedCategoryIds = $coach->assignedCategories->pluck('id')->toArray();
        $this->dispatch('open-modal.coach-assignments');
    }

    public function save(CoachAssignmentService $service): void
    {
        $coach = User::withoutGlobalScopes()->findOrFail($this->editingCoachId);

        $service->syncCategories($coach, $this->selectedCategoryIds);

        unset($this->coaches);
        $this->dispatch('close-modal.coach-assignments');
        $this->reset(['editingCoachId', 'selectedCategoryIds']);

        session()->flash('success', 'Categorias do treinador atualizadas com sucesso.');
    }
};
?>

<div>
    @session('success')
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ $value }}
        </div>
    @endsession

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Atribuições de Treinadores</h2>
    </div>

    @if ($this->coaches->isEmpty())
        <p class="text-gray-500 text-sm">Nenhum treinador cadastrado. <a href="{{ route('invitations.index') }}" wire:navigate class="text-primary-600 hover:underline">Convide um treinador</a>.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="pb-3 pr-4">Treinador</th>
                        <th class="pb-3 pr-4">Categorias atribuídas</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->coaches as $coach)
                        <tr wire:key="{{ $coach->id }}">
                            <td class="py-3 pr-4 font-medium">{{ $coach->name }}</td>
                            <td class="py-3 pr-4">
                                @if ($coach->assignedCategories->isEmpty())
                                    <span class="text-gray-400 text-xs">Nenhuma categoria atribuída</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($coach->assignedCategories->sortBy('min_age') as $category)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-3">
                                <button
                                    wire:click="openEdit('{{ $coach->id }}')"
                                    class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                                >
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-modal id="coach-assignments" title="Atribuir categorias">
        <div class="space-y-3">
            @if ($this->categories->isEmpty())
                <p class="text-sm text-gray-500">Nenhuma categoria ativa encontrada.</p>
            @else
                @foreach ($this->categories as $category)
                    <x-checkbox
                        :id="'cat-' . $category->id"
                        :label="$category->name . ' (' . $category->min_age . '–' . $category->max_age . ' anos)'"
                        wire:model="selectedCategoryIds"
                        value="{{ $category->id }}"
                    />
                @endforeach
            @endif
        </div>

        <x-slot name="footer">
            <x-button variant="secondary" type="button" x-on:click="show = false">
                Cancelar
            </x-button>
            <x-button type="button" wire:click="save">
                Salvar
            </x-button>
        </x-slot>
    </x-modal>
</div>
