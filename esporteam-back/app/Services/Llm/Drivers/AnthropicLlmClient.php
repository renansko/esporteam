<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\Contracts\LlmChatRequest;
use App\Services\Llm\Contracts\LlmChatResponse;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\LlmException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente Anthropic Messages API.
 *
 * @wiki app/brain/services/LlmFactory.md#anthropic
 */
class AnthropicLlmClient implements LlmClient
{
    /**
     * @param  array{api_key:?string,base_url:string,timeout_seconds:int,cost_per_1m_in_usd:float,cost_per_1m_out_usd:float}  $config
     */
    public function __construct(private readonly array $config) {}

    public function chat(LlmChatRequest $request): LlmChatResponse
    {
        $body = $this->buildBody($request);

        try {
            $response = Http::withHeaders([
                    'x-api-key'         => (string) ($this->config['api_key'] ?? ''),
                    'anthropic-version' => '2023-06-01',
                    'anthropic-beta'    => 'extended-cache-ttl-2025-04-11',
                    'content-type'      => 'application/json',
                ])
                ->timeout($this->config['timeout_seconds'])
                ->post(rtrim($this->config['base_url'], '/').'/v1/messages', $body);
        } catch (ConnectionException $e) {
            throw LlmException::timeout($e->getMessage());
        }

        if ($response->failed()) {
            throw LlmException::http($response->status(), (string) $response->body());
        }

        $json = $response->json();
        if (! is_array($json) || ! isset($json['content'])) {
            throw LlmException::parse('missing content[]');
        }

        $content = collect($json['content'] ?? [])
            ->filter(fn ($b) => ($b['type'] ?? null) === 'text')
            ->pluck('text')
            ->implode("\n");

        $usage = $json['usage'] ?? [];

        return new LlmChatResponse(
            content: $content,
            modelUsed: (string) ($json['model'] ?? $request->model ?? ''),
            tokensIn: (int) ($usage['input_tokens'] ?? 0),
            tokensOut: (int) ($usage['output_tokens'] ?? 0),
            tokensCached: (int) ($usage['cache_read_input_tokens'] ?? 0),
            finishReason: (string) ($json['stop_reason'] ?? 'stop'),
            raw: $json,
        );
    }

    /** @return array<string,mixed> */
    private function buildBody(LlmChatRequest $request): array
    {
        $systemBlocks = [];

        // Segmentos cacheáveis viram blocos antes do system base.
        foreach ($request->cacheSegments as $seg) {
            $systemBlocks[] = [
                'type'          => 'text',
                'text'          => (string) ($seg['content'] ?? ''),
                'cache_control' => ['type' => 'ephemeral', 'ttl' => '1h'],
            ];
        }
        if ($request->system !== '') {
            $systemBlocks[] = ['type' => 'text', 'text' => $request->system];
        }

        return [
            'model'       => $request->model ?? 'claude-haiku-4-5-20251001',
            'max_tokens'  => $request->maxTokens,
            'temperature' => $request->temperature,
            'system'      => $systemBlocks,
            'messages'    => $request->messages,
        ];
    }
}
