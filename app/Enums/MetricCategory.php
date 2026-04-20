<?php

namespace App\Enums;

enum MetricCategory: string
{
    case Technical = 'technical';
    case Physical  = 'physical';
    case Tactical  = 'tactical';
    case Attitude  = 'attitude';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Técnico',
            self::Physical  => 'Físico',
            self::Tactical  => 'Tático',
            self::Attitude  => 'Atitude',
        };
    }
}
