<?php

namespace App\Services\Llm\Contracts;

/**
 * DTO neutro de chat: drivers traduzem para o body próprio do provider.
 */
final readonly class LlmChatRequest
{
    /**
     * @param  list<array{role:string,content:string}>  $messages
     * @param  list<array{name:string,content:string}>  $cacheSegments
     *         Segmentos cacheáveis (Anthropic-specific). Ordem importa:
     *         system + few-shots primeiro, depois contexto dinâmico.
     *         Drivers que não suportam cache (OpenAI) ignoram silenciosamente.
     */
    public function __construct(
        public string $system,
        public array $messages,
        public ?string $model = null,
        public int $maxTokens = 4096,
        public float $temperature = 0.2,
        public string $responseFormat = 'json',
        public array $cacheSegments = [],
    ) {}
}
