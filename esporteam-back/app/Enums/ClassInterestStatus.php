<?php

namespace App\Enums;

enum ClassInterestStatus: string
{
    case Interested = 'interested';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
