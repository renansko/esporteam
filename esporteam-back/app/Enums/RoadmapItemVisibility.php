<?php

namespace App\Enums;

enum RoadmapItemVisibility: string
{
    case Internal = 'internal';
    case Public   = 'public';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
