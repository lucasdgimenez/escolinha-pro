<?php

use App\Models\Category;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Categorias')] class extends Component
{
    public function mount(): void
    {
        if (! auth()->user()->tenant_id) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public bool $showModal = false;
    public ?string $editingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|integer|min:0|max:99')]
    public int $minAge = 0;

    #[Validate('required|integer|min:0|max:99')]
    public int $maxAge = 0;

    #[Validate('required|numeric|min:0')]
    public string $monthlyFee = '0';

    public function validationAttributes(): array
    {
        return [
            'name'       => 'nome',
            'minAge'     => 'idade mínima',
            'maxAge'     => 'idade máxima',
            'monthlyFee' => 'mensalidade',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'O nome é obrigatório.',
            'minAge.required'     => 'A idade mínima é obrigatória.',
            'minAge.integer'      => 'A idade mínima deve ser um número inteiro.',
            'maxAge.required'     => 'A idade máxima é obrigatória.',
            'maxAge.integer'      => 'A idade máxima deve ser um número inteiro.',
            'monthlyFee.required' => 'A mensalidade é obrigatória.',
            'monthlyFee.numeric'  => 'A mensalidade deve ser um valor numérico.',
            'monthlyFee.min'      => 'A mensalidade não pode ser negativa.',
        ];
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('min_age')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'minAge', 'maxAge', 'monthlyFee', 'editingId']);
        $this->monthlyFee = '0';
        $this->showModal = true;
        $this->dispatch('open-modal.categories');
    }

    public function edit(string $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $id;
        $this->name = $category->name;
        $this->minAge = $category->min_age;
        $this->maxAge = $category->max_age;
        $this->monthlyFee = (string) $category->monthly_fee;
        $this->dispatch('open-modal.categories');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'        => $this->name,
            'min_age'     => $this->minAge,
            'max_age'     => $this->maxAge,
            'monthly_fee' => $this->monthlyFee,
        ];

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Categoria atualizada com sucesso.');
        } else {
            Category::create(array_merge($data, ['tenant_id' => auth()->user()->tenant_id]));
            session()->flash('success', 'Categoria criada com sucesso.');
        }

        $this->dispatch('close-modal.categories');
        $this->reset(['name', 'minAge', 'maxAge', 'monthlyFee', 'editingId']);
        $this->monthlyFee = '0';
        unset($this->categories);
    }

    public function toggleActive(string $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
        unset($this->categories);
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
        <h2 class="text-xl font-semibold text-gray-800">Categorias</h2>
        <x-button wire:click="openCreate" type="button">
            Nova categoria
        </x-button>
    </div>

    @if ($this->categories->isEmpty())
        <p class="text-gray-500 text-sm">Nenhuma categoria cadastrada.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="pb-3 pr-4">Nome</th>
                        <th class="pb-3 pr-4">Faixa etária</th>
                        <th class="pb-3 pr-4">Mensalidade</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->categories as $category)
                        <tr wire:key="{{ $category->id }}">
                            <td class="py-3 pr-4 font-medium">{{ $category->name }}</td>
                            <td class="py-3 pr-4">{{ $category->min_age }}–{{ $category->max_age }} anos</td>
                            <td class="py-3 pr-4">R$ {{ number_format($category->monthly_fee, 2, ',', '.') }}</td>
                            <td class="py-3 pr-4">
                                @if ($category->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                        Ativa
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                        Inativa
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 flex items-center gap-3">
                                <button
                                    wire:click="edit('{{ $category->id }}')"
                                    class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                                >
                                    Editar
                                </button>
                                <button
                                    wire:click="toggleActive('{{ $category->id }}')"
                                    wire:confirm="{{ $category->is_active ? 'Desativar esta categoria?' : 'Ativar esta categoria?' }}"
                                    class="text-sm font-medium {{ $category->is_active ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700' }}"
                                >
                                    {{ $category->is_active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-modal id="categories" :title="$editingId ? 'Editar categoria' : 'Nova categoria'">
        <form wire:submit="save" class="flex flex-col gap-4">
            <x-input
                name="name"
                label="Nome"
                wire:model="name"
                required
            />

            <div class="grid grid-cols-2 gap-4">
                <x-input
                    name="minAge"
                    label="Idade mínima"
                    type="number"
                    wire:model="minAge"
                    min="0"
                    max="99"
                    required
                />

                <x-input
                    name="maxAge"
                    label="Idade máxima"
                    type="number"
                    wire:model="maxAge"
                    min="0"
                    max="99"
                    required
                />
            </div>

            <x-input
                name="monthlyFee"
                label="Mensalidade (R$)"
                type="number"
                wire:model="monthlyFee"
                min="0"
                step="0.01"
                required
            />

            <x-slot name="footer">
                <x-button variant="secondary" type="button" x-on:click="show = false">
                    Cancelar
                </x-button>
                <x-button type="submit">
                    {{ $editingId ? 'Salvar' : 'Criar' }}
                </x-button>
            </x-slot>
        </form>
    </x-modal>
</div>
