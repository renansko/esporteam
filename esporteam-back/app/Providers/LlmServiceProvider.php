<?php

namespace App\Providers;

use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\LlmFactory;
use Illuminate\Support\ServiceProvider;

class LlmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LlmFactory::class, fn ($app) => new LlmFactory($app));

        // Em testes, ambos os clientes default ficam como Fakes — nenhum HTTP real
        // é disparado por padrão. Cada teste programa o que precisa.
        if (defined('APP_RUNNING_TESTS')) {
            $this->app->singleton(LlmClient::class, fn () => new \App\Services\Llm\Drivers\FakeLlmClient());
            $this->app->singleton(EmbeddingClient::class, fn () => new \App\Services\Llm\Drivers\FakeEmbeddingClient());
            return;
        }

        $this->app->bind(LlmClient::class, fn ($app) => $app->make(LlmFactory::class)->chat());
        $this->app->bind(EmbeddingClient::class, fn ($app) => $app->make(LlmFactory::class)->embedding());
    }
}
