<?php

namespace App\Enums;

enum BioSuggestionStatus: string
{
    case Generating = 'generating';
    case Generated = 'generated';
    case Accepted = 'accepted';
    case Failed = 'failed';
}
