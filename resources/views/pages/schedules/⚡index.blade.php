<?php

use App\Models\Category;
use App\Models\TrainingSchedule;
use App\Services\Training\TrainingScheduleService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cronogramas de Treino')] class extends Component
{
    #[Computed]
    public function schedules(): Collection
    {
        return TrainingSchedule::with('category')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function pause(int $id, TrainingScheduleService $service): void
    {
        $schedule = TrainingSchedule::findOrFail($id);
        $service->pause($schedule);
        unset($this->schedules);
        session()->flash('success', 'Cronograma pausado com sucesso.');
    }

    public function resume(int $id, TrainingScheduleService $service): void
    {
        $schedule = TrainingSchedule::findOrFail($id);
        $service->resume($schedule);
        unset($this->schedules);
        session()->flash('success', 'Cronograma reativado com sucesso.');
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
        <h2 class="text-xl font-semibold text-gray-800">Cronogramas de Treino</h2>
        <a href="{{ route('schedules.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
            Novo cronograma
        </a>
    </div>

    @if ($this->schedules->isEmpty())
        <p class="text-gray-500 text-sm">Nenhum cronograma cadastrado.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                    <tr>
                        <th class="pb-3 pr-4">Categoria</th>
                        <th class="pb-3 pr-4">Dia</th>
                        <th class="pb-3 pr-4">Horário</th>
                        <th class="pb-3 pr-4">Duração</th>
                        <th class="pb-3 pr-4">Local</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->schedules as $schedule)
                        <tr wire:key="{{ $schedule->id }}">
                            <td class="py-3 pr-4 font-medium">{{ $schedule->category->name }}</td>
                            <td class="py-3 pr-4">{{ $schedule->day_of_week->label() }}</td>
                            <td class="py-3 pr-4">{{ $schedule->start_time }}</td>
                            <td class="py-3 pr-4">{{ $schedule->duration_minutes }} min</td>
                            <td class="py-3 pr-4">{{ $schedule->location ?? '—' }}</td>
                            <td class="py-3 pr-4">
                                @if ($schedule->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Pausado</span>
                                @endif
                            </td>
                            <td class="py-3">
                                @if ($schedule->is_active)
                                    <button wire:click="pause({{ $schedule->id }})" wire:confirm="Pausar este cronograma?" class="text-yellow-600 hover:text-yellow-700 font-medium text-sm">
                                        Pausar
                                    </button>
                                @else
                                    <button wire:click="resume({{ $schedule->id }})" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                                        Reativar
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
