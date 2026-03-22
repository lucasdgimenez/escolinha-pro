<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class AcademyProfileForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    #[Validate('nullable|string|max:100')]
    public ?string $city = null;

    #[Validate('nullable|string|max:2')]
    public ?string $state = null;

    #[Validate('nullable|string|max:20')]
    public ?string $phone = null;

    #[Validate('nullable|string|regex:/^#[0-9A-Fa-f]{6}$/')]
    public ?string $primary_color = null;

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da academia é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'address.max' => 'O endereço não pode ter mais de 255 caracteres.',
            'city.max' => 'A cidade não pode ter mais de 100 caracteres.',
            'state.max' => 'Use a sigla do estado (ex: SP, RJ).',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'primary_color.regex' => 'A cor deve estar no formato hexadecimal (ex: #FF5733).',
        ];
    }
}
