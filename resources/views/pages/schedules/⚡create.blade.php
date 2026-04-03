<?php

use App\Livewire\Forms\TrainingScheduleForm;
use App\Models\Category;
use App\Services\Training\TrainingScheduleService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Novo Cronograma')] class extends Component
{
    public TrainingScheduleForm $form;

    #[Computed]
    public function categories(): Collection
    {
        return Category::where('is_active', true)->orderBy('min_age')->get();
    }

    public function save(TrainingScheduleService $service): void
    {
        $this->form->validate();

        $service->create([
            'category_id'      => $this->form->category_id,
            'day_of_week'      => $this->form->day_of_week,
            'start_time'       => $this->form->start_time,
            'duration_minutes' => $this->form->duration_minutes,
            'location'         => $this->form->location,
        ], auth()->user());

        session()->flash('success', 'Cronograma criado com sucesso.');

        $this->redirect(route('schedules.index'), navigate: true);
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Novo Cronograma</h2>
        <a href="{{ route('schedules.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">
            Voltar
        </a>
    </div>

    <form wire:submit="save" class="max-w-2xl flex flex-col gap-4">
        <x-select
            name="form.category_id"
            label="Categoria"
            wire:model="form.category_id"
            required
        >
            <option value="">Selecione uma categoria</option>
            @foreach ($this->categories as $category)
                <option value="{{ $category->id }}" @selected(old('form.category_id') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-select>

        <x-select
            name="form.day_of_week"
            label="Dia da semana"
            wire:model="form.day_of_week"
            required
        >
            <option value="">Selecione um dia</option>
            @foreach ($this->form->dayOfWeekOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('form.day_of_week') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </x-select>

        <x-input
            name="form.start_time"
            label="Horário de início"
            type="time"
            wire:model="form.start_time"
            required
        />

        <x-input
            name="form.duration_minutes"
            label="Duração (minutos)"
            type="number"
            min="15"
            max="240"
            wire:model="form.duration_minutes"
            required
        />

        <x-input
            name="form.location"
            label="Local (opcional)"
            wire:model="form.location"
        />

        <div class="flex gap-3 pt-2">
            <x-button type="submit">Salvar cronograma</x-button>
        </div>
    </form>
</div>
