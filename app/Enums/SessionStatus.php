<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled  => 'Agendada',
            self::InProgress => 'Em andamento',
            self::Completed  => 'Concluída',
            self::Cancelled  => 'Cancelada',
        };
    }
}
