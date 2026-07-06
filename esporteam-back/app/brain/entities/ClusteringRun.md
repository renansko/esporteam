# ClusteringRun

Uma execução do clustering por IA. Trackeia entrada (ideias_processed), saída (items_created/assigned), custo (token_usage_in/out), modelo, cache hit rate e fallback. Tem N `ClusteringDecision`.

## Schema

Ver migration `2026_05_24_000004_create_clustering_runs_table.php`. Resumo de campos não-óbvios:

| Coluna                      | Nota |
|-----------------------------|------|
| `status` (enum)             | `running \| done \| failed` |
| `started_at` / `completed_at` | timestamp |
| `llm_model` / `prompt_version` | identidade do que rodou — auditoria |
| `cache_hit_rate`            | % de input lido de cache (Anthropic) |
| `pre_cluster_bundles_count` | tamanho da redução semântica antes do LLM |
| `fallback_used`             | flag visível ao PM no histórico |
| `failure_reason`            | livre — message de exception ou nota do watchdog |

Índices:
- `(workspace_id, status)`
- `(workspace_id, started_at)`
- **partial unique** `(workspace_id) WHERE status='running'` (pgsql) — garante 1 run ativo por workspace

## Invariantes

- Apenas 1 run em `running` por workspace (lock no DB no pgsql; checagem no controller cobre sqlite/race conditions de baixa concorrência).
- Soft-delete **proibido** — auditoria precisa ser permanente.
- `rationale` (em `ClusteringDecision`) **não** deve conter PII (`author_email`).

Relacionamentos: `decisions()` (HasMany ClusteringDecision via `run_id`).
