<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use App\Services\Llm\Contracts\EmbeddingResponse;
use App\Services\Llm\LlmException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAiEmbeddingClient implements EmbeddingClient
{
    /**
     * @param  array{api_key:?string,base_url:string,timeout_seconds:int,embedding_model:string,embedding_dimensions:int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        $model = $request->model ?? $this->config['embedding_model'];

        try {
            $response = Http::withToken((string) ($this->config['api_key'] ?? ''))
                ->timeout($this->config['timeout_seconds'])
                ->post(rtrim($this->config['base_url'], '/').'/embeddings', [
                    'model' => $model,
                    'input' => $request->inputs,
                ]);
        } catch (ConnectionException $e) {
            throw LlmException::timeout($e->getMessage());
        }

        if ($response->failed()) {
            throw LlmException::http($response->status(), (string) $response->body());
        }

        $json = $response->json();
        $items = $json['data'] ?? [];

        $vectors = collect($items)
            ->map(fn ($d) => array_map('floatval', $d['embedding'] ?? []))
            ->values()
            ->all();

        return new EmbeddingResponse(
            vectors: $vectors,
            modelUsed: (string) ($json['model'] ?? $model),
            tokensUsed: (int) ($json['usage']['total_tokens'] ?? 0),
        );
    }
}
