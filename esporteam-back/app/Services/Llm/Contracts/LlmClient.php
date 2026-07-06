<?php

namespace App\Services\Llm\Contracts;

interface LlmClient
{
    /**
     * Faz uma chamada chat/completion. Implementadores devem traduzir
     * o LlmChatRequest neutro para o body do provider.
     *
     * Em caso de erro de transporte, deve lançar \App\Services\Llm\LlmException
     * com o motivo (timeout, HTTP 4xx/5xx, parse error inicial).
     */
    public function chat(LlmChatRequest $request): LlmChatResponse;
}
