# RoadmapItem

Item priorizado do roadmap — pode nascer manual, via clustering por IA, fallback determinístico ou competitor gap. Recebe N `Ideas` agregadas (`hasMany`).

## Schema

| Coluna            | Tipo                          | Nota |
|-------------------|-------------------------------|------|
| `id`              | bigint                        | PK |
| `workspace_id`    | unsignedBigInteger            | sem FK |
| `title`           | string(255)                   | obrigatório |
| `description`     | text                          | obrigatório |
| `status`          | string(32) enum `RoadmapItemStatus` | `em_analise \| planejado \| em_desenvolvimento \| lancado` |
| `visibility`      | string(16) enum `RoadmapItemVisibility` | default `internal` |
| `origin`          | string(32) enum `RoadmapItemOrigin` | `manual \| clustered \| fallback \| competitor_gap` |
| `score`           | decimal(8,4)                  | calculado server-side via `recomputeScore` |
| `score_breakdown` | json                          | `{impact, reach, effort}` em 1–5 |
| `votes_count`     | unsignedInteger               | default 0 |
| `timestamps`      |                               | |

Índices:
- `(workspace_id, score DESC)` (pgsql via DB::statement; sqlite via blueprint normal)
- `(workspace_id, status)`
- partial `(workspace_id, visibility) WHERE visibility='public'` (pgsql)

## Invariantes

- `workspace_id` sempre vem do claim JWT.
- Mutações em `score_breakdown` ou `votes_count` DEVEM chamar `recomputeScore` antes de salvar.
- `origin = 'fallback'` indica criação determinística — PM precisa revisar.

## Recompute score

`score = (impact * reach) / effort`. Escala 1–5. `effort=0` é coerced para 1.

Relacionamentos: `ideas()` (HasMany Idea), `clusteringDecisions()` (HasMany).
