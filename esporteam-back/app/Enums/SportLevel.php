<?php

namespace App\Enums;

enum SportLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Competitive = 'competitive';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
