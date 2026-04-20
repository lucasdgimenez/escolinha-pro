<?php

use App\Enums\NarrativeStatus;
use App\Enums\RoleSlug;
use App\Jobs\GenerateEvaluationNarrative;
use App\Models\Evaluation;
use App\Models\EvaluationNarrative;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Avaliação')] class extends Component
{
    public Evaluation $evaluation;

    #[Validate('nullable|string|max:4000', message: ['max' => 'O texto pode ter no máximo 4000 caracteres.'])]
    public string $editedText = '';

    public function mount(Evaluation $evaluation): void
    {
        if (auth()->user()->hasRole(RoleSlug::Coach)) {
            $assignedIds = auth()->user()->assignedCategories->pluck('id');

            if (! $assignedIds->contains($evaluation->category_id)) {
                abort(403);
            }
        }

        $this->evaluation = $evaluation;
        $this->editedText = $evaluation->narrative?->edited_text ?? '';
    }

    #[Computed]
    public function narrative(): ?EvaluationNarrative
    {
        return $this->evaluation->narrative()->first();
    }

    #[Computed]
    public function scoresByCategory(): Collection
    {
        return $this->evaluation
            ->metrics()
            ->with('metricKey')
            ->get()
            ->sortBy('metricKey.display_order')
            ->groupBy(fn ($m) => $m->metricKey->category->label());
    }

    public function saveNarrative(): void
    {
        $this->validate();

        $this->evaluation->narrative()->updateOrCreate(
            ['evaluation_id' => $this->evaluation->id],
            ['edited_text' => $this->editedText ?: null]
        );

        session()->flash('success', 'Narrativa salva com sucesso.');
    }

    public function generateNarrative(): void
    {
        GenerateEvaluationNarrative::dispatch($this->evaluation);
        unset($this->narrative);

        session()->flash('success', 'Solicitação de geração enviada.');
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('players.show', $this->evaluation->player_id) }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">
                ← Voltar para atleta
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Avaliação — {{ $this->evaluation->player->name }}</h2>
        </div>
    </div>

    @session('success')
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-800">
            {{ $value }}
        </div>
    @endsession

    <div class="max-w-3xl flex flex-col gap-6">

        {{-- Header info --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs text-gray-500">Data</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->evaluation->evaluated_at->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Categoria</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->evaluation->category->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Coach</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $this->evaluation->coach->name }}</dd>
                </div>
                @if ($this->evaluation->notes)
                    <div class="col-span-2 sm:col-span-4">
                        <dt class="text-xs text-gray-500">Observações do coach</dt>
                        <dd class="text-sm text-gray-700 mt-1">{{ $this->evaluation->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Scores by category --}}
        @foreach ($this->scoresByCategory as $categoryLabel => $metrics)
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">{{ $categoryLabel }}</h3>
                <div class="flex flex-col gap-3">
                    @foreach ($metrics as $metric)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">{{ $metric->metricKey->name }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $metric->score * 10 }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 w-8 text-right">{{ $metric->score }}/10</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Narrative section --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Narrativa IA</h3>
                <button
                    wire:click="generateNarrative"
                    type="button"
                    class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                >
                    Regenerar
                </button>
            </div>

            @if (! $this->narrative || $this->narrative->status === App\Enums\NarrativeStatus::Pending)
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="animate-spin h-4 w-4 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Gerando narrativa via IA...
                </div>
            @elseif ($this->narrative->status === App\Enums\NarrativeStatus::Generated)
                <div class="mb-3 p-3 bg-gray-50 rounded-lg text-sm text-gray-700 leading-relaxed">
                    {{ $this->narrative->ai_generated_text }}
                </div>
            @else
                <p class="text-sm text-red-600 mb-3">A geração automática falhou. Você pode escrever manualmente abaixo.</p>
            @endif

            <div class="mt-4 flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-700">
                    {{ $this->narrative?->status === App\Enums\NarrativeStatus::Generated ? 'Editar narrativa' : 'Narrativa manual' }}
                </label>
                <textarea
                    wire:model="editedText"
                    rows="5"
                    placeholder="Escreva ou edite a narrativa do atleta..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                ></textarea>
                @error('editedText') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <div>
                    <x-button wire:click="saveNarrative" type="button">Salvar narrativa</x-button>
                </div>
            </div>
        </div>
    </div>
</div>
