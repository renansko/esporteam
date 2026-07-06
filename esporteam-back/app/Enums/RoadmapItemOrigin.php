<?php

namespace App\Enums;

enum RoadmapItemOrigin: string
{
    case Manual        = 'manual';
    case Clustered     = 'clustered';
    case Fallback      = 'fallback';
    case CompetitorGap = 'competitor_gap';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
