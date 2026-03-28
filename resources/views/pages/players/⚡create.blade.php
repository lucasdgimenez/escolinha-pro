<?php

use App\Enums\DominantFoot;
use App\Enums\Position;
use App\Livewire\Forms\PlayerForm;
use App\Services\Player\PlayerService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Cadastrar Atleta')] class extends Component
{
    use WithFileUploads;

    public PlayerForm $form;

    #[Validate('nullable|image|max:2048', message: [
        'image' => 'O arquivo deve ser uma imagem.',
        'max' => 'A foto não pode ultrapassar 2 MB.',
    ])]
    public $photo = null;

    #[Validate('nullable|file|mimes:csv,txt|max:5120', message: [
        'file' => 'O arquivo deve ser um CSV.',
        'mimes' => 'O arquivo deve estar no formato CSV.',
        'max' => 'O arquivo não pode ultrapassar 5 MB.',
    ])]
    public $csvFile = null;

    public array $importResult = [];

    public function save(PlayerService $service): void
    {
        $this->form->validate();
        $this->validateOnly('photo');

        $service->create($this->form->toPlayerData(), $this->photo, auth()->user());

        session()->flash('success', 'Atleta cadastrado com sucesso.');
        $this->redirect(route('players.index'), navigate: true);
    }

    public function importCsv(PlayerService $service): void
    {
        $this->validateOnly('csvFile');

        if (! $this->csvFile) {
            $this->addError('csvFile', 'Selecione um arquivo CSV para importar.');
            return;
        }

        $this->importResult = $service->importFromCsv($this->csvFile, auth()->user());
        $this->csvFile = null;
    }

    public function downloadTemplate(PlayerService $service): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(
            fn () => print($service->csvTemplateContent()),
            'modelo-atletas.csv',
            ['Content-Type' => 'text/csv'],
        );
    }
};
?>

<div x-data="{ tab: 'form' }">
    @session('success')
        <div class="mb-6 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ $value }}
        </div>
    @endsession

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Cadastrar Atleta</h2>
        <a href="{{ route('players.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">
            ← Voltar para atletas
        </a>
    </div>

    {{-- Tab switcher --}}
    <div class="flex gap-1 mb-6 border-b border-gray-200">
        <button
            type="button"
            @click="tab = 'form'"
            :class="tab === 'form' ? 'border-b-2 border-primary-600 text-primary-700 font-medium' : 'text-gray-500 hover:text-gray-700'"
            class="px-4 py-2 text-sm transition-colors"
        >
            Formulário individual
        </button>
        <button
            type="button"
            @click="tab = 'csv'"
            :class="tab === 'csv' ? 'border-b-2 border-primary-600 text-primary-700 font-medium' : 'text-gray-500 hover:text-gray-700'"
            class="px-4 py-2 text-sm transition-colors"
        >
            Importar CSV
        </button>
    </div>

    {{-- Individual form --}}
    <div x-show="tab === 'form'">
        <form wire:submit="save" class="space-y-6 max-w-2xl">
            <x-input
                name="form.name"
                label="Nome do atleta"
                wire:model="form.name"
                required
            />

            <x-input
                name="form.dateOfBirth"
                label="Data de nascimento"
                type="date"
                wire:model="form.dateOfBirth"
                required
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-select name="form.position" label="Posição" wire:model="form.position" required>
                    <option value="">Selecione a posição</option>
                    @foreach (\App\Enums\Position::cases() as $position)
                        <option value="{{ $position->value }}" @selected(old('form.position') === $position->value)>
                            {{ $position->label() }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="form.dominantFoot" label="Pé dominante" wire:model="form.dominantFoot" required>
                    <option value="">Selecione o pé dominante</option>
                    @foreach (\App\Enums\DominantFoot::cases() as $foot)
                        <option value="{{ $foot->value }}" @selected(old('form.dominantFoot') === $foot->value)>
                            {{ $foot->label() }}
                        </option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto do atleta</label>

                @if ($photo)
                    <div class="mb-3">
                        <img
                            src="{{ $photo->temporaryUrl() }}"
                            alt="Pré-visualização"
                            class="h-20 w-20 rounded-full object-cover border border-gray-200"
                        />
                        <p class="mt-1 text-xs text-gray-500">Pré-visualização</p>
                    </div>
                @endif

                <input
                    type="file"
                    wire:model="photo"
                    accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                />
                @error('photo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 pt-6">
                <h3 class="text-base font-medium text-gray-800 mb-4">Dados do responsável</h3>

                <div class="space-y-4">
                    <x-input
                        name="form.guardianName"
                        label="Nome do responsável"
                        wire:model="form.guardianName"
                        required
                    />

                    <x-input
                        name="form.guardianEmail"
                        label="E-mail do responsável"
                        type="email"
                        wire:model="form.guardianEmail"
                        required
                    />

                    <x-input
                        name="form.guardianPhone"
                        label="Telefone do responsável"
                        wire:model="form.guardianPhone"
                    />
                </div>
            </div>

            <x-button type="submit">
                Cadastrar atleta
            </x-button>
        </form>
    </div>

    {{-- CSV import --}}
    <div x-show="tab === 'csv'" class="max-w-2xl">
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <p class="font-medium mb-1">Como importar atletas por CSV:</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Baixe o modelo de planilha abaixo</li>
                <li>Preencha os dados dos atletas conforme as colunas</li>
                <li>Salve o arquivo como CSV e envie</li>
            </ol>
        </div>

        <button wire:click="downloadTemplate" class="mb-6 text-sm text-primary-600 hover:text-primary-700 font-medium underline">
            Baixar modelo CSV
        </button>

        <form wire:submit="importCsv" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo CSV</label>
                <input
                    type="file"
                    wire:model="csvFile"
                    accept=".csv"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                />
                @error('csvFile')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit">
                Importar atletas
            </x-button>
        </form>

        @if (! empty($importResult))
            <div class="mt-6">
                <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm mb-3">
                    {{ $importResult['imported'] }} {{ $importResult['imported'] === 1 ? 'atleta importado' : 'atletas importados' }} com sucesso.
                </div>

                @if (! empty($importResult['errors']))
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm font-medium text-red-700 mb-2">Linhas com erro ({{ count($importResult['errors']) }}):</p>
                        <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
                            @foreach ($importResult['errors'] as $error)
                                <li>{{ $error['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
