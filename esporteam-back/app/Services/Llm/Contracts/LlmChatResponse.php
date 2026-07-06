<?php

namespace App\Services\Llm\Contracts;

final readonly class LlmChatResponse
{
    /**
     * @param  array<string,mixed>  $raw  body bruto do provider para trace.
     */
    public function __construct(
        public string $content,
        public string $modelUsed,
        public int $tokensIn,
        public int $tokensOut,
        public int $tokensCached,
        public string $finishReason,
        public array $raw = [],
    ) {}
}
