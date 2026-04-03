<?php

namespace App\Enums;

use Carbon\Carbon;

enum DayOfWeek: string
{
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public function label(): string
    {
        return match ($this) {
            self::Monday    => 'Segunda-feira',
            self::Tuesday   => 'Terça-feira',
            self::Wednesday => 'Quarta-feira',
            self::Thursday  => 'Quinta-feira',
            self::Friday    => 'Sexta-feira',
            self::Saturday  => 'Sábado',
            self::Sunday    => 'Domingo',
        };
    }

    public function carbonDay(): int
    {
        return match ($this) {
            self::Monday    => Carbon::MONDAY,
            self::Tuesday   => Carbon::TUESDAY,
            self::Wednesday => Carbon::WEDNESDAY,
            self::Thursday  => Carbon::THURSDAY,
            self::Friday    => Carbon::FRIDAY,
            self::Saturday  => Carbon::SATURDAY,
            self::Sunday    => Carbon::SUNDAY,
        };
    }
}
