<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use App\Services\Llm\Contracts\EmbeddingResponse;
use Closure;

class FakeEmbeddingClient implements EmbeddingClient
{
    private ?Closure $fn = null;

    /** @var list<EmbeddingRequest> */
    private array $calls = [];

    /**
     * Por padrão devolve vetor pseudo-determinístico a partir do hash do texto.
     * Use intercept() para fixtures customizadas.
     */
    public function intercept(Closure $fn): self
    {
        $this->fn = $fn;
        return $this;
    }

    /** @return list<EmbeddingRequest> */
    public function calls(): array
    {
        return $this->calls;
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        $this->calls[] = $request;
        if ($this->fn) {
            return ($this->fn)($request);
        }

        $vectors = array_map(
            static fn (string $text) => self::pseudoVector($text, 1536),
            $request->inputs
        );

        return new EmbeddingResponse(
            vectors: $vectors,
            modelUsed: 'fake-embedding',
            tokensUsed: count($request->inputs),
        );
    }

    /** @return list<float> */
    private static function pseudoVector(string $text, int $dim): array
    {
        $seed = crc32($text);
        mt_srand($seed);
        $out = [];
        for ($i = 0; $i < $dim; $i++) {
            $out[] = (mt_rand() / mt_getrandmax()) * 2 - 1;
        }
        // Normaliza (cosine fica estável independente de magnitude).
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $out))) ?: 1.0;
        return array_map(fn ($v) => $v / $norm, $out);
    }
}
