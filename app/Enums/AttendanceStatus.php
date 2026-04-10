<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present   = 'present';
    case Absent    = 'absent';
    case Justified = 'justified';

    public function label(): string
    {
        return match ($this) {
            self::Present   => 'Presente',
            self::Absent    => 'Ausente',
            self::Justified => 'Justificado',
        };
    }
}
