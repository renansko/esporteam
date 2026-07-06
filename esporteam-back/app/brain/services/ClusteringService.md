# ClusteringService

Orquestrador da run de clustering. Carrega contexto, decide entre LLM e fallback, persiste decisões, fecha a run e dispara o evento de broadcast.

## Dependências injetadas

- `LlmClusteringStrategy` — caminho feliz com LLM
- `FallbackClusteringStrategy` — caminho determinístico (1 Idea = 1 Item)
- `CircuitBreaker` — pula LLM se o workspace acumulou falhas em janela

## Fluxo

1. Carrega `Idea`s com `roadmap_item_id IS NULL` do workspace.
2. Carrega `RoadmapItem`s existentes (contexto para o LLM cluster).
3. Se `circuit_breaker.isOpen()` → fallback direto.
4. Caso contrário, tenta `LlmClusteringStrategy::execute`:
   - Pre-cluster com cosine similarity.
   - Renderiza prompt via `ClusteringPromptLoader`.
   - Chama `LlmClient::chat` com retry 1x (1s, 2s) antes de exceção.
   - Parseia JSON → valida por decisão via `ClusteringDecisionValidator`.
   - Persiste decisões. Decisões inválidas viram “ideia órfã”.
5. Pós-LLM, varre por **ideias órfãs** (rejected, hallucinated, esquecidas) e roda fallback nelas — dentro da mesma run.
6. Em catch de `LlmException`, registra failure no `CircuitBreaker` e roda fallback total.
7. Marca run como `done` (com counters) ou `failed` em catch global.

## Funções públicas

Detalhes em [`functions/ClusteringService.md`](../functions/ClusteringService.md).
