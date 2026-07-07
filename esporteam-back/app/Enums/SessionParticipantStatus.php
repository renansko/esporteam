<?php

namespace App\Enums;

enum SessionParticipantStatus: string
{
    case Joined = 'joined';
    case Left = 'left';
    case Invited = 'invited';
    case Interested = 'interested';
    case Approved = 'approved';
    case Declined = 'declined';
    case Removed = 'removed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function activeValues(): array
    {
        return [
            self::Joined->value,
            self::Approved->value,
        ];
    }

    public static function reservedValues(): array
    {
        return [
            self::Joined->value,
            self::Approved->value,
            self::Invited->value,
        ];
    }
}
