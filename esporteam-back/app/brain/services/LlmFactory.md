# LlmFactory

Hub multi-provider de LLM. Resolve `LlmClient` e `EmbeddingClient` por nome de driver (`anthropic`, `openai`, `fake`).

## Drivers

### anthropic
`AnthropicLlmClient` — POST `/v1/messages`. Mapeia `LlmChatRequest::cacheSegments` para `cache_control: {type:ephemeral, ttl:1h}` em blocks de system.

### openai
`OpenAiLlmClient` (chat) — POST `/v1/chat/completions`. `cacheSegments` é ignorado silenciosamente.
`OpenAiEmbeddingClient` (embeddings) — POST `/v1/embeddings`.

### fake (testes)
`FakeLlmClient` — programável via `queue(...)` e inspecionável via `calls()`.
`FakeEmbeddingClient` — devolve vetor pseudo-determinístico via hash do input.

## Configuração

- `config/llm.php` — driver default, modelo de clustering, budgets, custos.
- Em testes (`APP_RUNNING_TESTS=true`), `LlmServiceProvider` força bindings para Fakes — nenhum HTTP real é disparado.

## Como injetar

- Service classes recebem `LlmClient` ou `EmbeddingClient` direto via DI.
- Para escolher driver explicitamente: `app(LlmFactory::class)->chat('openai')`.
