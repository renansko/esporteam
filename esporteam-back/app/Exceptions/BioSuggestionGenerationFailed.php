<?php

namespace App\Exceptions;

use RuntimeException;

class BioSuggestionGenerationFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Não foi possível gerar uma sugestão de bio agora.');
    }
}
