<?php

use App\Services\Llm\Contracts\LlmChatRequest;
use App\Services\Llm\Drivers\OpenAiLlmClient;
use Illuminate\Support\Facades\Http;

it('sends Chat Completions body and parses response', function () {
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'model'   => 'gpt-4o-mini',
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"k":1}']]],
            'usage'   => ['prompt_tokens' => 50, 'completion_tokens' => 10, 'prompt_tokens_details' => ['cached_tokens' => 0]],
        ], 200),
    ]);

    $client = new OpenAiLlmClient([
        'api_key'         => 'sk-test',
        'base_url'        => 'https://api.openai.com/v1',
        'timeout_seconds' => 5,
    ]);

    $resp = $client->chat(new LlmChatRequest(
        system: 'You',
        messages: [['role' => 'user', 'content' => 'go']],
        cacheSegments: [['name' => 'x', 'content' => 'ignored']],
    ));

    expect($resp->content)->toBe('{"k":1}')
        ->and($resp->tokensIn)->toBe(50)
        ->and($resp->tokensOut)->toBe(10);

    Http::assertSent(function ($request) {
        $body = $request->data();
        // cacheSegments deve ser ignorado silenciosamente.
        return ! array_key_exists('cache_control', $body)
            && $body['response_format']['type'] === 'json_object'
            && $body['messages'][0]['role'] === 'system';
    });
});
