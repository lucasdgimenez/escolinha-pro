<?php

use App\Enums\MetricCategory;
use App\Enums\RoleSlug;
use App\Models\EvaluationMetricKey;
use App\Models\Player;
use App\Services\Evaluation\EvaluationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Nova Avaliação')] class extends Component
{
    public string $playerId = '';

    #[Validate('required|exists:players,id', message: ['required' => 'Selecione um atleta.', 'exists' => 'Atleta inválido.'])]
    public string $player_id = '';

    #[Validate('required|date', message: ['required' => 'Informe a data da avaliação.', 'date' => 'Data inválida.'])]
    public string $evaluated_at = '';

    #[Validate('nullable|string|max:2000', message: ['max' => 'As observações podem ter no máximo 2000 caracteres.'])]
    public string $notes = '';

    public array $scores = [];

    public function mount(): void
    {
        if ($this->playerId !== '') {
            $this->player_id = $this->playerId;
        }

        $this->evaluated_at = now()->format('Y-m-d');

        foreach ($this->metricKeys as $key) {
            $this->scores[$key->id] = '';
        }
    }

    #[Computed]
    public function players(): Collection
    {
        $user = auth()->user();

        if ($user->hasRole(RoleSlug::Coach)) {
            $categoryIds = $user->assignedCategories->pluck('id');

            return Player::whereIn('category_id', $categoryIds)
                ->orderBy('name')
                ->get();
        }

        return Player::orderBy('name')->get();
    }

    #[Computed]
    public function metricKeys(): Collection
    {
        return EvaluationMetricKey::orderBy('category')
            ->orderBy('display_order')
            ->get();
    }

    #[Computed]
    public function metricsByCategory(): Collection
    {
        return $this->metricKeys->groupBy(fn ($key) => $key->category->value);
    }

    public function save(EvaluationService $service): void
    {
        $this->validate();

        if (! $this->hasValidScores()) {
            return;
        }

        $evaluation = $service->create(
            [
                'player_id'    => $this->player_id,
                'evaluated_at' => $this->evaluated_at,
                'notes'        => $this->notes ?: null,
            ],
            array_map('intval', $this->scores),
            auth()->user()
        );

        session()->flash('success', 'Avaliação registrada com sucesso. A narrativa está sendo gerada.');

        $this->redirect(route('evaluations.show', $evaluation), navigate: true);
    }

    private function hasValidScores(): bool
    {
        $valid = true;

        foreach ($this->metricKeys as $key) {
            $score = $this->scores[$key->id] ?? '';

            if ($score === '' || ! is_numeric($score)) {
                $this->addError("scores.{$key->id}", "Nota obrigatória para {$key->name}.");
                $valid = false;
            } elseif ((int) $score < 1 || (int) $score > 10) {
                $this->addError("scores.{$key->id}", "A nota para {$key->name} deve ser entre 1 e 10.");
                $valid = false;
            }
        }

        return $valid;
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            @if ($player_id)
                <a href="{{ route('players.show', $player_id) }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">
                    ← Voltar para atleta
                </a>
            @endif
            <h2 class="text-xl font-semibold text-gray-800">Nova Avaliação</h2>
        </div>
    </div>

    <form wire:submit="save" class="max-w-3xl flex flex-col gap-6">

        {{-- Player & date --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 flex flex-col gap-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Dados da Avaliação</h3>

            <x-select
                name="player_id"
                label="Atleta"
                wire:model="player_id"
                required
            >
                <option value="">Selecione um atleta</option>
                @foreach ($this->players as $player)
                    <option value="{{ $player->id }}" @selected($player_id == $player->id)>
                        {{ $player->name }}
                    </option>
                @endforeach
            </x-select>

            <x-input
                name="evaluated_at"
                label="Data da avaliação"
                type="date"
                wire:model="evaluated_at"
                required
            />

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700">Observações (opcional)</label>
                <textarea
                    wire:model="notes"
                    rows="3"
                    placeholder="Contexto adicional sobre a avaliação..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                ></textarea>
                @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Metric scores grouped by category --}}
        @foreach ($this->metricsByCategory as $categoryValue => $keys)
            @php $category = App\Enums\MetricCategory::from($categoryValue); @endphp
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">
                    {{ $category->label() }}
                </h3>
                <div class="flex flex-col gap-3">
                    @foreach ($keys as $key)
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-sm text-gray-700 flex-1">{{ $key->name }}</label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    max="10"
                                    wire:model="scores.{{ $key->id }}"
                                    class="w-16 rounded-lg border border-gray-300 px-2 py-1 text-sm text-center text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('scores.'.$key->id) border-red-400 @enderror"
                                    placeholder="1-10"
                                />
                                <span class="text-xs text-gray-400">/10</span>
                            </div>
                        </div>
                        @error('scores.'.$key->id)
                            <p class="text-xs text-red-600 text-right">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex gap-3 pb-6">
            <x-button type="submit">Salvar avaliação</x-button>
        </div>
    </form>
</div>
