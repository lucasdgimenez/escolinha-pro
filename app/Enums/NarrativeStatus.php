<?php

namespace App\Enums;

enum NarrativeStatus: string
{
    case Pending   = 'pending';
    case Generated = 'generated';
    case Failed    = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Aguardando geração',
            self::Generated => 'Gerada',
            self::Failed    => 'Falha na geração',
        };
    }
}
