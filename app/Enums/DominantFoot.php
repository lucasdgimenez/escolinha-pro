<?php

namespace App\Enums;

enum DominantFoot: string
{
    case Right = 'right';
    case Left = 'left';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Right => 'Direito',
            self::Left  => 'Esquerdo',
            self::Both  => 'Ambidestro',
        };
    }
}
