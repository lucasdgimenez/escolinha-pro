<?php

namespace App\Livewire\Forms;

use App\Enums\DayOfWeek;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TrainingScheduleForm extends Form
{
    #[Validate('required|exists:categories,id')]
    public string $category_id = '';

    #[Validate('required')]
    public string $day_of_week = '';

    #[Validate('required|date_format:H:i')]
    public string $start_time = '';

    #[Validate('required|integer|min:15|max:240')]
    public int $duration_minutes = 90;

    #[Validate('nullable|string|max:255')]
    public ?string $location = null;

    public function messages(): array
    {
        return [
            'category_id.required' => 'Selecione uma categoria.',
            'category_id.exists'   => 'Categoria inválida.',
            'day_of_week.required' => 'Selecione o dia da semana.',
            'start_time.required'  => 'Informe o horário de início.',
            'start_time.date_format' => 'O horário deve estar no formato HH:MM.',
            'duration_minutes.required' => 'Informe a duração.',
            'duration_minutes.integer'  => 'A duração deve ser um número inteiro.',
            'duration_minutes.min'      => 'A duração mínima é de 15 minutos.',
            'duration_minutes.max'      => 'A duração máxima é de 240 minutos.',
            'location.max'         => 'O local pode ter no máximo 255 caracteres.',
        ];
    }

    public function dayOfWeekOptions(): array
    {
        return collect(DayOfWeek::cases())
            ->mapWithKeys(fn (DayOfWeek $day) => [$day->value => $day->label()])
            ->toArray();
    }
}
