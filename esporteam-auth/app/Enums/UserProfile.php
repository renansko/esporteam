<?php

namespace App\Enums;

enum UserProfile: string
{
    case Admin = 'admin';
    case User = 'user';
    case Teacher = 'teacher';
    case Helper = 'helper';

    public function level(): int
    {
        return match ($this) {
            self::Admin => 100,
            self::Teacher => 50,
            self::Helper => 25,
            self::User => 10,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $profile) => $profile->value, self::cases());
    }
}
