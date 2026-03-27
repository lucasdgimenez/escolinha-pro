<?php

namespace App\Enums;

enum PlayerPosition: string
{
    case Goalkeeper = 'goalkeeper';
    case Defender = 'defender';
    case Midfielder = 'midfielder';
    case Forward = 'forward';

    public function label(): string
    {
        return match ($this) {
            self::Goalkeeper => 'Goleiro',
            self::Defender   => 'Defensor',
            self::Midfielder => 'Meio-campista',
            self::Forward    => 'Atacante',
        };
    }
}
