<?php

use App\Enums\RoleSlug;
use App\Models\Category;
use App\Services\Training\TrainingScheduleService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Nova Sessão Avulsa')] class extends Component
{
    #[Validate('required|exists:categories,id', message: ['required' => 'Selecione uma categoria.', 'exists' => 'Categoria inválida.'])]
    public string $category_id = '';

    #[Validate('required|date|after_or_equal:today', message: ['required' => 'Informe a data da sessão.', 'date' => 'Data inválida.', 'after_or_equal' => 'A data não pode ser no passado.'])]
    public string $session_date = '';

    #[Validate('required|date_format:H:i', message: ['required' => 'Informe o horário.', 'date_format' => 'O horário deve estar no formato HH:MM.'])]
    public string $start_time = '';

    #[Validate('required|integer|min:15|max:240', message: ['required' => 'Informe a duração.', 'integer' => 'Duração deve ser um número.', 'min' => 'Duração mínima de 15 minutos.', 'max' => 'Duração máxima de 240 minutos.'])]
    public int $duration_minutes = 90;

    #[Validate('nullable|string|max:255', message: ['max' => 'O local pode ter no máximo 255 caracteres.'])]
    public ?string $location = null;

    #[Computed]
    public function categories(): Collection
    {
        $user = auth()->user();

        if ($user->hasRole(RoleSlug::Coach)) {
            return $user->assignedCategories()->where('is_active', true)->orderBy('min_age')->get();
        }

        return Category::where('is_active', true)->orderBy('min_age')->get();
    }

    public function save(TrainingScheduleService $service): void
    {
        $this->validate();

        $service->createOneOff([
            'category_id'      => $this->category_id,
            'session_date'     => $this->session_date,
            'start_time'       => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'location'         => $this->location,
        ], auth()->user());

        session()->flash('success', 'Sessão avulsa criada com sucesso.');

        $this->redirect(route('dashboard'), navigate: true);
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Nova Sessão Avulsa</h2>
        <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">
            Voltar
        </a>
    </div>

    <form wire:submit="save" class="max-w-2xl flex flex-col gap-4">
        <x-select
            name="category_id"
            label="Categoria"
            wire:model="category_id"
            required
        >
            <option value="">Selecione uma categoria</option>
            @foreach ($this->categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-select>

        <x-input
            name="session_date"
            label="Data da sessão"
            type="date"
            wire:model="session_date"
            required
        />

        <x-input
            name="start_time"
            label="Horário de início"
            type="time"
            wire:model="start_time"
            required
        />

        <x-input
            name="duration_minutes"
            label="Duração (minutos)"
            type="number"
            min="15"
            max="240"
            wire:model="duration_minutes"
            required
        />

        <x-input
            name="location"
            label="Local (opcional)"
            wire:model="location"
        />

        <div class="flex gap-3 pt-2">
            <x-button type="submit">Criar sessão</x-button>
        </div>
    </form>
</div>
