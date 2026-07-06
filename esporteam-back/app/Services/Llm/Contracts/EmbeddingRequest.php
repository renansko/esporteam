<?php

namespace App\Services\Llm\Contracts;

final readonly class EmbeddingRequest
{
    /**
     * @param  list<string>  $inputs
     */
    public function __construct(
        public array $inputs,
        public ?string $model = null,
    ) {}
}
