<?php

namespace App\Exceptions;

use RuntimeException;

class UnsafeBioSuggestion extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A sugestão recebida não passou pelas validações de segurança.');
    }
}
