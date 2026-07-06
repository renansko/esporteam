<?php

namespace App\Services\Llm\Contracts;

interface EmbeddingClient
{
    public function embed(EmbeddingRequest $request): EmbeddingResponse;
}
