<?php

namespace App\Services\Llm;

use RuntimeException;

class LlmException extends RuntimeException
{
    public static function timeout(string $message): self
    {
        return new self('LLM timeout: '.$message);
    }

    public static function http(int $status, string $body): self
    {
        return new self("LLM HTTP {$status}: ".\Str::limit($body, 200));
    }

    public static function parse(string $reason): self
    {
        return new self('LLM parse error: '.$reason);
    }
}
