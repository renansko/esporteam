<?php

use App\Services\Llm\Contracts\LlmChatRequest;
use App\Services\Llm\Drivers\AnthropicLlmClient;
use App\Services\Llm\LlmException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->config = [
        'api_key'             => 'k-test',
        'base_url'            => 'https://api.anthropic.com',
        'timeout_seconds'     => 5,
        'cost_per_1m_in_usd'  => 0.25,
        'cost_per_1m_out_usd' => 1.25,
    ];
});

it('builds Messages API body with cache_control on cache segments', function () {
    Http::fake([
        'api.anthropic.com/v1/messages' => Http::response([
            'model'       => 'claude-haiku-4-5-20251001',
            'stop_reason' => 'end_turn',
            'content'     => [['type' => 'text', 'text' => '{"ok":true}']],
            'usage'       => ['input_tokens' => 100, 'output_tokens' => 20, 'cache_read_input_tokens' => 50],
        ], 200),
    ]);

    $client = new AnthropicLlmClient($this->config);
    $resp = $client->chat(new LlmChatRequest(
        system: 'You cluster ideas.',
        messages: [['role' => 'user', 'content' => 'go']],
        model: 'claude-haiku-4-5-20251001',
        cacheSegments: [['name' => 'workspace_items', 'content' => 'prev items list']],
    ));

    expect($resp->content)->toBe('{"ok":true}')
        ->and($resp->tokensIn)->toBe(100)
        ->and($resp->tokensOut)->toBe(20)
        ->and($resp->tokensCached)->toBe(50)
        ->and($resp->finishReason)->toBe('end_turn');

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->url() === 'https://api.anthropic.com/v1/messages'
            && $request->method() === 'POST'
            && $request->hasHeader('x-api-key', 'k-test')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && is_array($body['system'])
            && $body['system'][0]['cache_control']['type'] === 'ephemeral'
            && $body['system'][0]['cache_control']['ttl'] === '1h'
            && $body['system'][1]['text'] === 'You cluster ideas.'
            && $body['messages'][0]['role'] === 'user';
    });
});

it('throws LlmException on HTTP error', function () {
    Http::fake([
        'api.anthropic.com/v1/messages' => Http::response(['error' => 'overload'], 529),
    ]);

    $client = new AnthropicLlmClient($this->config);
    $client->chat(new LlmChatRequest(system: 's', messages: [['role' => 'user', 'content' => 'x']]));
})->throws(LlmException::class);

it('throws LlmException on missing content', function () {
    Http::fake([
        'api.anthropic.com/v1/messages' => Http::response(['foo' => 'bar'], 200),
    ]);

    $client = new AnthropicLlmClient($this->config);
    $client->chat(new LlmChatRequest(system: 's', messages: [['role' => 'user', 'content' => 'x']]));
})->throws(LlmException::class);
