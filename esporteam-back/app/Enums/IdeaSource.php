<?php

namespace App\Enums;

enum IdeaSource: string
{
    case Manual        = 'manual';
    case Csv           = 'csv';
    case PublicForm    = 'public_form';
    case CompetitorGap = 'competitor_gap';
}
