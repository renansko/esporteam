<?php

namespace App\Enums;

enum ClassOfferingStatus: string
{
    case Open = 'open';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
