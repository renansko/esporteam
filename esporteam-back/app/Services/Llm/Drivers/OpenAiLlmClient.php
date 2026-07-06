<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\Contracts\LlmChatRequest;
use App\Services\Llm\Contracts\LlmChatResponse;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\LlmException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente OpenAI Chat Completions. Ignora cacheSegments silenciosamente.
 *
 * @wiki app/brain/services/LlmFactory.md#openai
 */
class OpenAiLlmClient implements LlmClient
{
    /**
     * @param  array{api_key:?string,base_url:string,timeout_seconds:int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function chat(LlmChatRequest $request): LlmChatResponse
    {
        $messages = $request->system !== ''
            ? [['role' => 'system', 'content' => $request->system], ...$request->messages]
            : $request->messages;

        $body = [
            'model'       => $request->model ?? 'gpt-4o-mini',
            'messages'    => $messages,
            'max_tokens'  => $request->maxTokens,
            'temperature' => $request->temperature,
        ];

        if ($request->responseFormat === 'json') {
            $body['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withToken((string) ($this->config['api_key'] ?? ''))
                ->timeout($this->config['timeout_seconds'])
                ->post(rtrim($this->config['base_url'], '/').'/chat/completions', $body);
        } catch (ConnectionException $e) {
            throw LlmException::timeout($e->getMessage());
        }

        if ($response->failed()) {
            throw LlmException::http($response->status(), (string) $response->body());
        }

        $json = $response->json();
        $choice = $json['choices'][0] ?? null;
        if (! $choice || ! isset($choice['message']['content'])) {
            throw LlmException::parse('missing choices[0].message.content');
        }

        $usage = $json['usage'] ?? [];

        return new LlmChatResponse(
            content: (string) $choice['message']['content'],
            modelUsed: (string) ($json['model'] ?? $body['model']),
            tokensIn: (int) ($usage['prompt_tokens'] ?? 0),
            tokensOut: (int) ($usage['completion_tokens'] ?? 0),
            tokensCached: (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0),
            finishReason: (string) ($choice['finish_reason'] ?? 'stop'),
            raw: $json,
        );
    }
}
