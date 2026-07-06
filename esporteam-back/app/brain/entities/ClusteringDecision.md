# ClusteringDecision

Decisão atômica do clustering: "Idea X foi `assign` ao Item Y por motivo Z" ou "Idea X gerou item novo Y por motivo Z". É a unidade de auditoria do trace.

## Schema

| Coluna            | Nota |
|-------------------|------|
| `id`              | PK |
| `run_id`          | FK → `clustering_runs.id` ON DELETE CASCADE |
| `idea_id`         | FK → `ideas.id` ON DELETE CASCADE |
| `roadmap_item_id` | FK → `roadmap_items.id` ON DELETE CASCADE |
| `action`          | enum `assign \| create` |
| `rationale`       | text livre — frase do LLM, ou nota do fallback |
| `created_at`     | timestamp (sem `updated_at`) |

Índices: `(run_id)`, `(idea_id)`.

## Quando o `rationale` se forma

- LLM (happy path) → frase devolvida pelo modelo.
- Fallback (`FallbackClusteringStrategy`) → mensagem fixa: `"Fallback determinístico: <motivo>. Item criado 1:1 a partir da Idea #X."`.
- Pre-cluster siblings → rationale do representante acrescido de `[pre-cluster bundle: a,b,c]`.

Relacionamentos: `run()`, `idea()`, `roadmapItem()`.
