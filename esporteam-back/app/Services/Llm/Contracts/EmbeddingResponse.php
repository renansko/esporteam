<?php

namespace App\Services\Llm\Contracts;

final readonly class EmbeddingResponse
{
    /**
     * @param  list<list<float>>  $vectors  uma posição por input.
     */
    public function __construct(
        public array $vectors,
        public string $modelUsed,
        public int $tokensUsed,
    ) {}
}
