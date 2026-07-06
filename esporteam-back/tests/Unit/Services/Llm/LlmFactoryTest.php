<?php

use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\Drivers\AnthropicLlmClient;
use App\Services\Llm\Drivers\FakeEmbeddingClient;
use App\Services\Llm\Drivers\FakeLlmClient;
use App\Services\Llm\Drivers\OpenAiEmbeddingClient;
use App\Services\Llm\Drivers\OpenAiLlmClient;
use App\Services\Llm\LlmFactory;

it('resolves anthropic as default LlmClient', function () {
    config()->set('llm.default', 'anthropic');
    $factory = new LlmFactory(app());
    expect($factory->chat())->toBeInstanceOf(AnthropicLlmClient::class);
});

it('resolves openai driver explicitly', function () {
    $factory = new LlmFactory(app());
    expect($factory->chat('openai'))->toBeInstanceOf(OpenAiLlmClient::class);
});

it('resolves openai embedding by default', function () {
    $factory = new LlmFactory(app());
    expect($factory->embedding())->toBeInstanceOf(OpenAiEmbeddingClient::class);
});

it('returns same instance on repeated resolution', function () {
    $factory = new LlmFactory(app());
    expect($factory->chat('anthropic'))->toBe($factory->chat('anthropic'));
});

it('allows injecting fakes via setChat', function () {
    $factory = new LlmFactory(app());
    $fake = new FakeLlmClient();
    $factory->setChat('anthropic', $fake);
    expect($factory->chat('anthropic'))->toBe($fake);
});

it('binds Fake clients in the container during tests', function () {
    // Em testes, LlmServiceProvider mapeia os contracts para Fakes — nenhum HTTP real é disparado.
    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class)
        ->and(app(EmbeddingClient::class))->toBeInstanceOf(FakeEmbeddingClient::class);
});

it('throws on unknown driver', function () {
    (new LlmFactory(app()))->chat('weird');
})->throws(InvalidArgumentException::class);
