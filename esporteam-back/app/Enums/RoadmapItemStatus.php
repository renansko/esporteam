<?php

namespace App\Enums;

enum RoadmapItemStatus: string
{
    case EmAnalise         = 'em_analise';
    case Planejado         = 'planejado';
    case EmDesenvolvimento = 'em_desenvolvimento';
    case Lancado           = 'lancado';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
