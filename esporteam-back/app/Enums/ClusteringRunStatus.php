<?php

namespace App\Enums;

enum ClusteringRunStatus: string
{
    case Running = 'running';
    case Done    = 'done';
    case Failed  = 'failed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
