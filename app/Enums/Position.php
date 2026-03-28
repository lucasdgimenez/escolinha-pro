<?php

namespace App\Enums;

enum Position: string
{
    case Goalkeeper = 'goleiro';
    case Defender = 'zagueiro';
    case LeftBack = 'lateral-esquerdo';
    case RightBack = 'lateral-direito';
    case CenterBack = 'beque';
    case DefensiveMidfielder = 'volante';
    case Midfielder = 'meia';
    case AttackingMidfielder = 'meia-atacante';
    case LeftWing = 'ponta-esquerda';
    case RightWing = 'ponta-direita';
    case Forward = 'centroavante';
    case Striker = 'atacante';

    public function label(): string
    {
        return match ($this) {
            self::Goalkeeper => 'Goleiro',
            self::Defender => 'Zagueiro',
            self::LeftBack => 'Lateral Esquerdo',
            self::RightBack => 'Lateral Direito',
            self::CenterBack => 'Beque',
            self::DefensiveMidfielder => 'Volante',
            self::Midfielder => 'Meia',
            self::AttackingMidfielder => 'Meia-Atacante',
            self::LeftWing => 'Ponta Esquerda',
            self::RightWing => 'Ponta Direita',
            self::Forward => 'Centroavante',
            self::Striker => 'Atacante',
        };
    }
}
