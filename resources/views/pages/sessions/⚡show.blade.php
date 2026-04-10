<?php

use App\Enums\AttendanceStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Player;
use App\Models\SessionAttendance;
use App\Models\TrainingSession;
use App\Models\User;
use App\Notifications\PlayerAbsentNotification;
use App\Services\Training\TrainingSessionService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Sessão de Treino')] class extends Component
{
    public TrainingSession $session;

    #[Validate('nullable|string|max:5000')]
    public string $notes = '';

    #[Validate('nullable|integer|min:1|max:5')]
    public string $rating = '';

    private TrainingSessionService $service;

    public function boot(TrainingSessionService $service): void
    {
        $this->service = $service;
    }

    public function mount(TrainingSession $session): void
    {
        if (auth()->user()->hasRole(RoleSlug::Coach)) {
            $assignedIds = auth()->user()->assignedCategories->pluck('id');

            if (! $assignedIds->contains($session->category_id)) {
                abort(403);
            }
        }

        $this->session = $session;
        $this->notes   = $session->notes ?? '';
        $this->rating  = $session->rating !== null ? (string) $session->rating : '';
    }

    #[Computed]
    public function players(): Collection
    {
        return $this->session->category->players()->orderBy('name')->get();
    }

    #[Computed]
    public function attendanceMap(): array
    {
        return SessionAttendance::where('training_session_id', $this->session->id)
            ->pluck('status', 'player_id')
            ->toArray();
    }

    public function saveNotes(): void
    {
        $this->validate();

        $this->session->update([
            'notes'  => $this->notes ?: null,
            'rating' => $this->rating !== '' ? (int) $this->rating : null,
        ]);

        $this->session->refresh();
        session()->flash('success', 'Observações salvas.');
    }

    public function markAttendance(int $playerId, string $status): void
    {
        $allowedStatuses = [SessionStatus::InProgress, SessionStatus::Completed];

        if (! in_array($this->session->status, $allowedStatuses, true)) {
            return;
        }

        SessionAttendance::updateOrCreate(
            [
                'training_session_id' => $this->session->id,
                'player_id'           => $playerId,
            ],
            [
                'tenant_id' => $this->session->tenant_id,
                'status'    => $status,
            ]
        );

        if ($status === AttendanceStatus::Absent->value) {
            $player = Player::find($playerId);
            $parent = User::where('email', $player->guardian_email)
                ->where('tenant_id', $this->session->tenant_id)
                ->first();

            if ($parent) {
                $parent->notify(new PlayerAbsentNotification($player, $this->session));
            }
        }

        unset($this->attendanceMap);
    }

    public function startSession(): void
    {
        $this->service->transitionTo($this->session, SessionStatus::InProgress);
        $this->session->refresh();
        session()->flash('success', 'Sessão iniciada.');
    }

    public function completeSession(): void
    {
        $this->service->transitionTo($this->session, SessionStatus::Completed);
        $this->session->refresh();
        session()->flash('success', 'Sessão concluída.');
    }

    public function cancelSession(): void
    {
        $this->service->transitionTo($this->session, SessionStatus::Cancelled);
        $this->session->refresh();
        session()->flash('success', 'Sessão cancelada.');
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
        <div>
            <a href="{{ route('sessions.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">
                ← Voltar para sessões
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Sessão de Treino</h2>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex flex-col gap-6">

            {{-- Session info card --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">{{ $this->session->category->name }}</p>
                        <p class="text-2xl font-semibold text-gray-800">{{ $this->session->session_date->format('d/m/Y') }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $this->session->start_time }} · {{ $this->session->duration_minutes }} min
                            @if ($this->session->location)
                                · {{ $this->session->location }}
                            @endif
                        </p>
                    </div>
                    @php
                        $badgeClass = match ($this->session->status) {
                            SessionStatus::Scheduled  => 'bg-blue-100 text-blue-700',
                            SessionStatus::InProgress => 'bg-yellow-100 text-yellow-700',
                            SessionStatus::Completed  => 'bg-green-100 text-green-700',
                            SessionStatus::Cancelled  => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-sm font-medium {{ $badgeClass }}">
                        {{ $this->session->status->label() }}
                    </span>
                </div>

                {{-- Action buttons --}}
                @if ($this->session->status === SessionStatus::Scheduled || $this->session->status === SessionStatus::InProgress)
                    <div class="flex gap-2 pt-3 border-t border-gray-100">
                        @if ($this->session->status === SessionStatus::Scheduled)
                            <x-button wire:click="startSession" variant="primary">
                                Iniciar sessão
                            </x-button>
                        @endif

                        @if ($this->session->status === SessionStatus::InProgress)
                            <x-button wire:click="completeSession" variant="primary">
                                Concluir sessão
                            </x-button>
                        @endif

                        <x-button
                            wire:click="cancelSession"
                            wire:confirm="Deseja cancelar esta sessão? Esta ação não pode ser desfeita."
                            variant="danger"
                        >
                            Cancelar sessão
                        </x-button>
                    </div>
                @endif
            </div>

            {{-- Notes & Rating --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Observações e Avaliação</h3>

                <div class="flex flex-col gap-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea
                            id="notes"
                            wire:model="notes"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                            placeholder="Anotações sobre a sessão..."
                        ></textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="max-w-xs">
                        <x-select name="rating" label="Avaliação do grupo" wire:model="rating">
                            <option value="">Sem avaliação</option>
                            <option value="1" @selected($rating == '1')>1 — Muito ruim</option>
                            <option value="2" @selected($rating == '2')>2 — Ruim</option>
                            <option value="3" @selected($rating == '3')>3 — Regular</option>
                            <option value="4" @selected($rating == '4')>4 — Bom</option>
                            <option value="5" @selected($rating == '5')>5 — Excelente</option>
                        </x-select>
                        @error('rating') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-button wire:click="saveNotes" variant="primary">
                            Salvar observações
                        </x-button>
                    </div>
                </div>
            </div>

        </div>

        {{-- Players / Attendance sidebar --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
                @if ($this->session->status === SessionStatus::InProgress || $this->session->status === SessionStatus::Completed)
                    Presença
                @else
                    Jogadores da categoria
                @endif
                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    {{ $this->players->count() }}
                </span>
            </h3>

            @if ($this->players->isEmpty())
                <p class="text-sm text-gray-400">Nenhum jogador nesta categoria.</p>
            @elseif ($this->session->status === SessionStatus::InProgress || $this->session->status === SessionStatus::Completed)
                {{-- Attendance marking mode --}}
                <ul class="divide-y divide-gray-100">
                    @foreach ($this->players as $player)
                        @php $currentStatus = $this->attendanceMap[$player->id] ?? null; @endphp
                        <li class="py-2.5">
                            <p class="text-sm font-medium text-gray-800 mb-1.5">{{ $player->name }}</p>
                            <div class="flex gap-1">
                                <button
                                    wire:click="markAttendance({{ $player->id }}, '{{ AttendanceStatus::Present->value }}')"
                                    wire:key="attendance-{{ $player->id }}-present"
                                    class="flex-1 px-2 py-1 text-xs font-medium rounded border transition-colors
                                        {{ $currentStatus === AttendanceStatus::Present
                                            ? 'bg-green-600 border-green-600 text-white'
                                            : 'bg-white border-gray-200 text-gray-600 hover:border-green-400 hover:text-green-700' }}"
                                >
                                    Presente
                                </button>
                                <button
                                    wire:click="markAttendance({{ $player->id }}, '{{ AttendanceStatus::Absent->value }}')"
                                    wire:key="attendance-{{ $player->id }}-absent"
                                    class="flex-1 px-2 py-1 text-xs font-medium rounded border transition-colors
                                        {{ $currentStatus === AttendanceStatus::Absent
                                            ? 'bg-red-600 border-red-600 text-white'
                                            : 'bg-white border-gray-200 text-gray-600 hover:border-red-400 hover:text-red-700' }}"
                                >
                                    Ausente
                                </button>
                                <button
                                    wire:click="markAttendance({{ $player->id }}, '{{ AttendanceStatus::Justified->value }}')"
                                    wire:key="attendance-{{ $player->id }}-justified"
                                    class="flex-1 px-2 py-1 text-xs font-medium rounded border transition-colors
                                        {{ $currentStatus === AttendanceStatus::Justified
                                            ? 'bg-yellow-500 border-yellow-500 text-white'
                                            : 'bg-white border-gray-200 text-gray-600 hover:border-yellow-400 hover:text-yellow-700' }}"
                                >
                                    Justif.
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                {{-- Read-only player list --}}
                <ul class="divide-y divide-gray-100">
                    @foreach ($this->players as $player)
                        <li class="py-2.5">
                            <p class="text-sm font-medium text-gray-800">{{ $player->name }}</p>
                            <p class="text-xs text-gray-500">{{ $player->position->label() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
