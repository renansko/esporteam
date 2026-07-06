<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\Contracts\LlmChatRequest;
use App\Services\Llm\Contracts\LlmChatResponse;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\LlmException;
use Closure;

/**
 * Fake programável para testes.
 *
 * - queue() empilha respostas / exceções.
 * - intercept() registra um closure que recebe LlmChatRequest e devolve LlmChatResponse|Throwable|string(json).
 * - calls() devolve todos os requests recebidos para asserts.
 */
class FakeLlmClient implements LlmClient
{
    /** @var list<LlmChatResponse|\Throwable|Closure> */
    private array $queue = [];

    /** @var list<LlmChatRequest> */
    private array $calls = [];

    public function queue(LlmChatResponse|\Throwable|Closure $next): self
    {
        $this->queue[] = $next;
        return $this;
    }

    /** @return list<LlmChatRequest> */
    public function calls(): array
    {
        return $this->calls;
    }

    public function chat(LlmChatRequest $request): LlmChatResponse
    {
        $this->calls[] = $request;
        $next = array_shift($this->queue);

        if ($next === null) {
            throw LlmException::parse('FakeLlmClient queue is empty');
        }
        if ($next instanceof Closure) {
            $next = $next($request);
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }
        return $next;
    }
}
