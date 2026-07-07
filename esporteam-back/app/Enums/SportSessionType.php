<?php

namespace App\Enums;

enum SportSessionType: string
{
    case Match = 'partida';
    case Training = 'treino';
    case Run = 'corrida';
    case OpenClass = 'aula_aberta';
    case Meetup = 'encontro';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
