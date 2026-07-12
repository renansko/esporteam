<?php

namespace App\Services\Llm;

use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\Drivers\AnthropicLlmClient;
use App\Services\Llm\Drivers\FakeEmbeddingClient;
use App\Services\Llm\Drivers\FakeLlmClient;
use App\Services\Llm\Drivers\OpenAiEmbeddingClient;
use App\Services\Llm\Drivers\OpenAiLlmClient;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * Hub multi-provider. Resolve LlmClient / EmbeddingClient pelo nome do driver.
 *
 * @wiki app/brain/services/LlmFactory.md
 */
class LlmFactory
{
    /** @var array<string,LlmClient> */
    private array $chatResolved = [];

    /** @var array<string,EmbeddingClient> */
    private array $embeddingResolved = [];

    public function __construct(private readonly Application $app) {}

    public function chat(?string $driver = null): LlmClient
    {
        $name = $driver ?? config('llm.default');
        return $this->chatResolved[$name] ??= $this->makeChat($name);
    }

    public function embedding(?string $driver = null): EmbeddingClient
    {
        $name = $driver ?? config('llm.default_for_embeddings', 'openai');
        return $this->embeddingResolved[$name] ??= $this->makeEmbedding($name);
    }

    /** Permite injetar fakes em testes. */
    public function setChat(string $driver, LlmClient $client): void
    {
        $this->chatResolved[$driver] = $client;
    }

    public function setEmbedding(string $driver, EmbeddingClient $client): void
    {
        $this->embeddingResolved[$driver] = $client;
    }

    private function makeChat(string $name): LlmClient
    {
        return match ($name) {
            'anthropic' => new AnthropicLlmClient(config('llm.providers.anthropic') ?? []),
            'openai'    => new OpenAiLlmClient(config('llm.providers.openai') ?? []),
            'fake'      => new FakeLlmClient(),
            default     => throw new InvalidArgumentException("Unknown LLM driver: {$name}"),
        };
    }

    private function makeEmbedding(string $name): EmbeddingClient
    {
        return match ($name) {
            'openai' => new OpenAiEmbeddingClient(config('llm.providers.openai') ?? []),
            'fake'   => new FakeEmbeddingClient(),
            default  => throw new InvalidArgumentException("Unknown embedding driver: {$name}"),
        };
    }
}
