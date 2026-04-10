<?php

use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sessões de Treino')] class extends Component
{
    public string $categoryId = '';
    public string $view = 'list';
    public int $currentYear;
    public int $currentMonth;

    public function mount(): void
    {
        $this->currentYear  = now()->year;
        $this->currentMonth = now()->month;
    }

    #[Computed]
    public function categories(): Collection
    {
        $user = auth()->user();

        if ($user->hasRole(RoleSlug::Coach)) {
            return $user->assignedCategories()->where('is_active', true)->orderBy('min_age')->get();
        }

        return Category::where('is_active', true)->orderBy('min_age')->get();
    }

    #[Computed]
    public function sessions(): Collection
    {
        $query = TrainingSession::with('category')
            ->orderBy('session_date', 'desc')
            ->orderBy('start_time', 'desc');

        if (auth()->user()->hasRole(RoleSlug::Coach)) {
            $assignedIds = auth()->user()->assignedCategories->pluck('id');
            $query->whereIn('category_id', $assignedIds);
        }

        if ($this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        return $query->get();
    }

    #[Computed]
    public function sessionsByDate(): Collection
    {
        $query = TrainingSession::with('category')
            ->whereYear('session_date', $this->currentYear)
            ->whereMonth('session_date', $this->currentMonth)
            ->orderBy('start_time');

        if (auth()->user()->hasRole(RoleSlug::Coach)) {
            $assignedIds = auth()->user()->assignedCategories->pluck('id');
            $query->whereIn('category_id', $assignedIds);
        }

        if ($this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        return $query->get()->groupBy(fn ($s) => $s->session_date->format('Y-m-d'));
    }

    public function previousMonth(): void
    {
        if ($this->currentMonth === 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }

        unset($this->sessionsByDate);
    }

    public function nextMonth(): void
    {
        if ($this->currentMonth === 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }

        unset($this->sessionsByDate);
    }

    public function updatedCategoryId(): void
    {
        unset($this->sessions);
        unset($this->sessionsByDate);
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
        <h2 class="text-xl font-semibold text-gray-800">Sessões de Treino</h2>
        <div class="flex items-center gap-3">
            {{-- View toggle --}}
            <div class="flex gap-0.5 bg-gray-100 border border-gray-200 rounded-lg p-0.5">
                <button
                    wire:click="$set('view', 'list')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $view === 'list' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                    Lista
                </button>
                <button
                    wire:click="$set('view', 'calendar')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors {{ $view === 'calendar' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                    Calendário
                </button>
            </div>

            <a href="{{ route('sessions.create') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700">
                Nova sessão
            </a>
        </div>
    </div>

    <div class="mb-4 max-w-xs">
        <x-select
            name="categoryId"
            label="Filtrar por categoria"
            wire:model.live="categoryId"
        >
            <option value="">Todas as categorias</option>
            @foreach ($this->categories as $category)
                <option value="{{ $category->id }}" @selected($this->categoryId == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-select>
    </div>

    @if ($view === 'list')
        @if ($this->sessions->isEmpty())
            <p class="text-gray-500 text-sm">Nenhuma sessão encontrada.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase border-b border-gray-200">
                        <tr>
                            <th class="pb-3 pr-4">Data</th>
                            <th class="pb-3 pr-4">Categoria</th>
                            <th class="pb-3 pr-4">Início</th>
                            <th class="pb-3 pr-4">Duração</th>
                            <th class="pb-3 pr-4">Local</th>
                            <th class="pb-3 pr-4">Status</th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($this->sessions as $session)
                            <tr wire:key="{{ $session->id }}">
                                <td class="py-3 pr-4 font-medium">{{ $session->session_date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4">{{ $session->category->name }}</td>
                                <td class="py-3 pr-4">{{ $session->start_time }}</td>
                                <td class="py-3 pr-4">{{ $session->duration_minutes }} min</td>
                                <td class="py-3 pr-4">{{ $session->location ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    @php
                                        $badgeClass = match ($session->status) {
                                            SessionStatus::Scheduled  => 'bg-blue-100 text-blue-700',
                                            SessionStatus::InProgress => 'bg-yellow-100 text-yellow-700',
                                            SessionStatus::Completed  => 'bg-green-100 text-green-700',
                                            SessionStatus::Cancelled  => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                        {{ $session->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <a href="{{ route('sessions.show', $session) }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                                        Ver detalhes
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        @php
            $monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            $monthLabel  = $monthNames[$currentMonth - 1] . ' ' . $currentYear;
            $firstDay    = Carbon::createFromDate($currentYear, $currentMonth, 1);
            $startDow    = $firstDay->dayOfWeek; // 0=Sunday
            $daysInMonth = $firstDay->daysInMonth;
            $totalCells  = $startDow + $daysInMonth;
            $rows        = (int) ceil($totalCells / 7);
        @endphp

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            {{-- Month navigation --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                <button wire:click="previousMonth" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    ←
                </button>
                <span class="text-sm font-semibold text-gray-800">{{ $monthLabel }}</span>
                <button wire:click="nextMonth" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    →
                </button>
            </div>

            <table class="w-full table-fixed">
                <thead>
                    <tr class="border-b border-gray-100">
                        @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dow)
                            <th class="py-2 text-xs font-medium text-gray-400 text-center uppercase tracking-wide">{{ $dow }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for ($row = 0; $row < $rows; $row++)
                        <tr>
                            @for ($dow = 0; $dow < 7; $dow++)
                                @php $day = $row * 7 + $dow - $startDow + 1; @endphp
                                @if ($day < 1 || $day > $daysInMonth)
                                    <td class="border-t border-gray-100 p-1.5 bg-gray-50 align-top h-20"></td>
                                @else
                                    @php
                                        $dateKey     = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                        $daySessions = $this->sessionsByDate->get($dateKey, collect());
                                        $isToday     = $dateKey === now()->format('Y-m-d');
                                    @endphp
                                    <td class="border-t border-gray-100 p-1.5 align-top h-20">
                                        <span class="inline-flex items-center justify-center w-6 h-6 text-sm font-medium {{ $isToday ? 'bg-primary-600 text-white rounded-full' : 'text-gray-700' }}">
                                            {{ $day }}
                                        </span>
                                        @foreach ($daySessions as $session)
                                            @php
                                                $dotClass = match ($session->status) {
                                                    SessionStatus::Scheduled  => 'bg-blue-500',
                                                    SessionStatus::InProgress => 'bg-yellow-500',
                                                    SessionStatus::Completed  => 'bg-green-500',
                                                    SessionStatus::Cancelled  => 'bg-gray-400',
                                                };
                                            @endphp
                                            <a href="{{ route('sessions.show', $session) }}" wire:navigate class="flex items-center gap-1 mt-0.5 text-xs text-gray-700 hover:text-primary-700 truncate">
                                                <span class="flex-shrink-0 w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                                <span class="truncate">{{ $session->category->name }}</span>
                                            </a>
                                        @endforeach
                                    </td>
                                @endif
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @endif
</div>
