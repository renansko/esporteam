<?php

namespace App\Enums;

enum SessionParticipantStatus: string
{
    case Joined = 'joined';
    case Left = 'left';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
