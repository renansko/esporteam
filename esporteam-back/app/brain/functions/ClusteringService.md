# ClusteringService — Funções

## executeRun

```php
public function executeRun(ClusteringRun $run): void
```

Carrega Ideas + Items do workspace, escolhe estratégia, persiste, encerra a run.

**Side effects:**
- Cria `RoadmapItem`s (origin `clustered` ou `fallback`).
- Atualiza `Idea.roadmap_item_id`.
- Cria `ClusteringDecision`s (auditoria).
- Atualiza `ClusteringRun` (status, métricas, custos).
- Loga via canal `clustering` (started/completed/failed/retry/fallback).
- Pode incrementar `CircuitBreaker` por falhas LLM.

**Não dispara** o evento `ClusteringRunCompleted` — quem faz isso é o `ClusterIdeasJob` no `finally`.

**Erros não tratados:** marca run como `failed` e re-throw para o handler do job.
