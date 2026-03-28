<?php

use App\Enums\DominantFoot;
use App\Enums\PlayerPosition;
use App\Livewire\Forms\PlayerForm;
use App\Services\Players\PlayerService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Novo atleta')] class extends Component
{
    use WithFileUploads;

    public PlayerForm $form;

    #[Validate('nullable|image|max:2048', message: [
        'image' => 'O arquivo deve ser uma imagem.',
        'max' => 'A foto não pode ultrapassar 2 MB.',
    ])]
    public $photo = null;

    public function save(PlayerService $service): void
    {
        $this->form->validate();
        $this->validate();

        $service->create($this->form->all(), $this->photo, auth()->user());

        session()->flash('success', 'Atleta cadastrado com sucesso.');

        $this->redirect(route('players.index'), navigate: true);
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
        <h2 class="text-xl font-semibold text-gray-800">Novo atleta</h2>
        <a href="{{ route('players.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">
            Voltar
        </a>
    </div>

    <form wire:submit="save" class="max-w-2xl flex flex-col gap-4">
        <x-input
            name="form.name"
            label="Nome do atleta"
            wire:model="form.name"
            required
        />

        <x-input
            name="form.date_of_birth"
            label="Data de nascimento"
            type="date"
            wire:model="form.date_of_birth"
            required
        />

        <div class="grid grid-cols-2 gap-4">
            <x-select
                name="form.position"
                label="Posição"
                wire:model="form.position"
                required
            >
                <option value="">Selecione...</option>
                @foreach (PlayerPosition::cases() as $position)
                    <option value="{{ $position->value }}" @selected($this->form->position === $position->value)>
                        {{ $position->label() }}
                    </option>
                @endforeach
            </x-select>

            <x-select
                name="form.dominant_foot"
                label="Pé dominante"
                wire:model="form.dominant_foot"
                required
            >
                <option value="">Selecione...</option>
                @foreach (DominantFoot::cases() as $foot)
                    <option value="{{ $foot->value }}" @selected($this->form->dominant_foot === $foot->value)>
                        {{ $foot->label() }}
                    </option>
                @endforeach
            </x-select>
        </div>

        <div class="flex flex-col gap-1">
            <label for="photo" class="text-sm font-medium text-gray-700">Foto</label>

            @if ($photo)
                <div class="mb-2">
                    <img
                        src="{{ $photo->temporaryUrl() }}"
                        alt="Pré-visualização"
                        class="h-20 w-20 rounded-full object-cover border border-gray-200"
                    />
                </div>
            @endif

            <input
                type="file"
                id="photo"
                wire:model="photo"
                accept="image/*"
                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
            />
            @error('photo')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <hr class="border-gray-200" />

        <h3 class="text-base font-semibold text-gray-700">Responsável</h3>

        <x-input
            name="form.guardian_name"
            label="Nome do responsável"
            wire:model="form.guardian_name"
            required
        />

        <div class="grid grid-cols-2 gap-4">
            <x-input
                name="form.guardian_email"
                label="E-mail do responsável"
                type="email"
                wire:model="form.guardian_email"
                required
            />

            <x-input
                name="form.guardian_phone"
                label="Telefone do responsável"
                type="tel"
                wire:model="form.guardian_phone"
            />
        </div>

        <div class="flex gap-3 pt-2">
            <x-button type="submit">
                Cadastrar atleta
            </x-button>
            <a href="{{ route('players.index') }}" wire:navigate>
                <x-button type="button" variant="secondary">
                    Cancelar
                </x-button>
            </a>
        </div>
    </form>
</div>
