<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBioContext extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Adicione uma Modalidade ou uma orientação para gerar sua bio.');
    }
}
