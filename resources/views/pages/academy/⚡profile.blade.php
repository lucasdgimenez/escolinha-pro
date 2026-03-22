<?php

use App\Livewire\Forms\AcademyProfileForm;
use App\Models\Tenant;
use App\Services\Academy\AcademyService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Perfil da Academia')] class extends Component
{
    use WithFileUploads;

    public AcademyProfileForm $form;
    public ?Tenant $tenant = null;

    #[Validate('nullable|image|max:2048')]
    public $logo;

    public function mount(): void
    {
        $this->tenant = auth()->user()->tenant;

        if (! $this->tenant) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->form->fill([
            'name'          => $this->tenant->name ?? '',
            'address'       => $this->tenant->address ?? '',
            'city'          => $this->tenant->city ?? '',
            'state'         => $this->tenant->state ?? '',
            'phone'         => $this->tenant->phone ?? '',
            'primary_color' => $this->tenant->primary_color ?? '',
        ]);
    }

    public function save(AcademyService $service): void
    {
        $this->form->validate();
        $this->validateOnly('logo');

        $service->updateProfile(
            $this->tenant,
            $this->form->all(),
            $this->logo,
        );

        $this->tenant->refresh();
        $this->logo = null;

        session()->flash('success', 'Perfil da academia atualizado com sucesso.');
    }
};
?>

<div>
    @session('success')
        <div class="mb-6 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ $value }}
        </div>
    @endsession

    <h2 class="text-xl font-semibold text-gray-800 mb-6">Perfil da Academia</h2>

    <form wire:submit="save" class="space-y-6 max-w-2xl">
        <x-input
            name="form.name"
            label="Nome da academia"
            wire:model="form.name"
            required
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-input
                name="form.city"
                label="Cidade"
                wire:model="form.city"
            />

            <x-input
                name="form.state"
                label="Estado (UF)"
                wire:model="form.state"
                maxlength="2"
            />
        </div>

        <x-input
            name="form.address"
            label="Endereço"
            wire:model="form.address"
        />

        <x-input
            name="form.phone"
            label="Telefone"
            wire:model="form.phone"
        />

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cor principal</label>
            <div class="flex items-center gap-3">
                <input
                    type="color"
                    wire:model="form.primary_color"
                    class="h-10 w-16 rounded border border-gray-300 cursor-pointer p-1"
                />
                <input
                    type="text"
                    wire:model="form.primary_color"
                    placeholder="#3B82F6"
                    maxlength="7"
                    class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
            </div>
            @error('form.primary_color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>

            @if ($tenant?->logo_path)
                <div class="mb-3">
                    <img
                        src="{{ Storage::disk('public')->url($tenant->logo_path) }}"
                        alt="Logo atual"
                        class="h-16 w-auto rounded border border-gray-200 object-contain"
                    />
                    <p class="mt-1 text-xs text-gray-500">Logo atual</p>
                </div>
            @endif

            @if ($logo)
                <div class="mb-3">
                    <img
                        src="{{ $logo->temporaryUrl() }}"
                        alt="Pré-visualização"
                        class="h-16 w-auto rounded border border-gray-200 object-contain"
                    />
                    <p class="mt-1 text-xs text-gray-500">Pré-visualização</p>
                </div>
            @endif

            <input
                type="file"
                wire:model="logo"
                accept="image/*"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
            />
            @error('logo')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <x-button type="submit" class="mt-4">
            Salvar alterações
        </x-button>
    </form>
</div>
