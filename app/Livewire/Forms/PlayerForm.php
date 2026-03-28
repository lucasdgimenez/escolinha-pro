<?php

namespace App\Livewire\Forms;

use App\Enums\DominantFoot;
use App\Enums\Position;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PlayerForm extends Form
{
    public string $name = '';

    public string $dateOfBirth = '';

    public string $position = '';

    public string $dominantFoot = '';

    public string $guardianName = '';

    public string $guardianEmail = '';

    public ?string $guardianPhone = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date', 'before:today'],
            'position' => ['required', Rule::in(array_column(Position::cases(), 'value'))],
            'dominantFoot' => ['required', Rule::in(array_column(DominantFoot::cases(), 'value'))],
            'guardianName' => ['required', 'string', 'max:255'],
            'guardianEmail' => ['required', 'email', 'max:255'],
            'guardianPhone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do atleta é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'dateOfBirth.required' => 'A data de nascimento é obrigatória.',
            'dateOfBirth.date' => 'Informe uma data de nascimento válida.',
            'dateOfBirth.before' => 'A data de nascimento deve ser anterior a hoje.',
            'position.required' => 'A posição é obrigatória.',
            'position.in' => 'Selecione uma posição válida.',
            'dominantFoot.required' => 'O pé dominante é obrigatório.',
            'dominantFoot.in' => 'Selecione um pé dominante válido.',
            'guardianName.required' => 'O nome do responsável é obrigatório.',
            'guardianName.max' => 'O nome do responsável não pode ter mais de 255 caracteres.',
            'guardianEmail.required' => 'O e-mail do responsável é obrigatório.',
            'guardianEmail.email' => 'Informe um e-mail válido para o responsável.',
            'guardianEmail.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'guardianPhone.max' => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }

    public function toPlayerData(): array
    {
        return [
            'name' => $this->name,
            'date_of_birth' => $this->dateOfBirth,
            'position' => $this->position,
            'dominant_foot' => $this->dominantFoot,
            'guardian_name' => $this->guardianName,
            'guardian_email' => $this->guardianEmail,
            'guardian_phone' => $this->guardianPhone,
        ];
    }
}
