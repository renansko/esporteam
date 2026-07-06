<?php

namespace App\Enums;

enum ProfileVisibility: string
{
    case Public = 'public';
    case Hidden = 'hidden';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
