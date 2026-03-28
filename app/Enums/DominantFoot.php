<?php

namespace App\Enums;

enum DominantFoot: string
{
    case Right = 'direito';
    case Left = 'esquerdo';
    case Both = 'ambidestro';

    public function label(): string
    {
        return match ($this) {
            self::Right => 'Direito',
            self::Left => 'Esquerdo',
            self::Both => 'Ambidestro',
        };
    }
}
