<?php

namespace App\Enums;

enum SportGoal: string
{
    case Play = 'jogar';
    case Train = 'treinar';
    case Learn = 'aprender';
    case Compete = 'competir';
    case MakeFriends = 'fazer-amigos';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
