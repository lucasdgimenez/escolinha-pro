<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class PlayerForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|date|before:today')]
    public string $date_of_birth = '';

    #[Validate('required|string')]
    public string $position = '';

    #[Validate('required|string')]
    public string $dominant_foot = '';

    #[Validate('required|string|max:255')]
    public string $guardian_name = '';

    #[Validate('required|email|max:255')]
    public string $guardian_email = '';

    #[Validate('nullable|string|max:20')]
    public string $guardian_phone = '';

    public function messages(): array
    {
        return [
            'name.required'           => 'O nome é obrigatório.',
            'name.max'                => 'O nome não pode ter mais de 255 caracteres.',
            'date_of_birth.required'  => 'A data de nascimento é obrigatória.',
            'date_of_birth.date'      => 'Informe uma data de nascimento válida.',
            'date_of_birth.before'    => 'A data de nascimento deve ser anterior à data atual.',
            'position.required'       => 'A posição é obrigatória.',
            'dominant_foot.required'  => 'O pé dominante é obrigatório.',
            'guardian_name.required'  => 'O nome do responsável é obrigatório.',
            'guardian_name.max'       => 'O nome do responsável não pode ter mais de 255 caracteres.',
            'guardian_email.required' => 'O e-mail do responsável é obrigatório.',
            'guardian_email.email'    => 'Informe um e-mail válido para o responsável.',
            'guardian_email.max'      => 'O e-mail do responsável não pode ter mais de 255 caracteres.',
            'guardian_phone.max'      => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }
}
