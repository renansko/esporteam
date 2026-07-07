<?php

namespace App\Enums;

enum SportSessionStatus: string
{
    case Open = 'open';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
