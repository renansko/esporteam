<?php

namespace App\Enums;

enum ClusteringDecisionAction: string
{
    case Assign = 'assign';
    case Create = 'create';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
