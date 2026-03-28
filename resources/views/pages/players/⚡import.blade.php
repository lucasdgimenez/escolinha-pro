<?php

use App\Services\Players\PlayerService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Importar atletas')] class extends Component
{
    use WithFileUploads;

    #[Validate('required|mimes:csv,txt|max:1024')]
    public $csvFile = null;

    public ?array $importResult = null;

    public function downloadTemplate()
    {
        $headers = ['name', 'date_of_birth', 'position', 'dominant_foot', 'guardian_name', 'guardian_email', 'guardian_phone'];

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fclose($handle);
        }, 'modelo-atletas.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(PlayerService $service): void
    {
        $this->validate();

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');

        $rows = [];
        $headers = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $line;

                continue;
            }

            if (count($line) === count($headers)) {
                $rows[] = array_combine($headers, $line);
            }
        }

        fclose($handle);

        $this->importResult = $service->importFromCsv($rows, auth()->user());
        $this->reset('csvFile');
    }

    public function messages(): array
    {
        return [
            'csvFile.required' => 'Selecione um arquivo CSV.',
            'csvFile.mimes'    => 'O arquivo deve ser no formato CSV.',
            'csvFile.max'      => 'O arquivo não pode ser maior que 1 MB.',
        ];
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Importar atletas</h2>
        <a href="{{ route('players.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">
            Voltar
        </a>
    </div>

    <div class="max-w-xl">
        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
            <p class="font-medium mb-2">Como importar:</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Baixe o modelo de planilha abaixo</li>
                <li>Preencha os dados dos atletas</li>
                <li>Salve como CSV e faça o upload</li>
            </ol>
            <div class="mt-3">
                <button wire:click="downloadTemplate" type="button" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium text-sm">
                    Baixar modelo CSV
                </button>
            </div>
        </div>

        <form wire:submit="import" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <label for="csvFile" class="text-sm font-medium text-gray-700">
                    Arquivo CSV <span class="text-error-500" aria-hidden="true">*</span>
                </label>
                <input
                    type="file"
                    id="csvFile"
                    wire:model="csvFile"
                    accept=".csv,text/csv"
                    class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                />
                @error('csvFile')
                    <p class="text-xs text-error-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-button type="submit">
                    Importar
                </x-button>
            </div>
        </form>

        @if ($importResult !== null)
            <div class="mt-6 space-y-4">
                <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ $importResult['created'] }} {{ $importResult['created'] === 1 ? 'atleta importado' : 'atletas importados' }} com sucesso.
                </div>

                @if (! empty($importResult['errors']))
                    <div>
                        <p class="text-sm font-medium text-red-700 mb-2">{{ count($importResult['errors']) }} {{ count($importResult['errors']) === 1 ? 'erro encontrado' : 'erros encontrados' }}:</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-2">Linha</th>
                                        <th class="px-4 py-2">Erro</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($importResult['errors'] as $error)
                                        <tr>
                                            <td class="px-4 py-2">{{ $error['row'] }}</td>
                                            <td class="px-4 py-2 text-red-600">{{ $error['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
