# Idea

Entrada bruta de qualquer fonte (manual, CSV, formulário público, gap de concorrente). É o que o cliente, PM ou IA acabou de capturar — ainda **não priorizado**.

Quando agrupado pela IA, recebe `roadmap_item_id` apontando para o `RoadmapItem` correspondente. Itens com `roadmap_item_id IS NULL` são "não clusterizados" e candidatos a virar input do clustering.

## Schema

| Coluna             | Tipo                          | Nota                                                         |
|--------------------|-------------------------------|--------------------------------------------------------------|
| `id`               | bigint                        | PK                                                           |
| `workspace_id`     | unsignedBigInteger            | sem FK — workspace vive em esporteam-workspace                   |
| `source`           | string (enum `IdeaSource`)    | Manual / Csv / PublicForm / CompetitorGap                    |
| `title`            | string(255), nullable         | opcional                                                     |
| `description`      | text, NOT NULL                | conteúdo principal                                           |
| `author_email`     | string, nullable              | mutator → lowercase + trim                                   |
| `roadmap_item_id`  | unsignedBigInteger, nullable  | FK lógico para `roadmap_items` (sem constraint do banco)     |
| `source_file_id`   | unsignedBigInteger, nullable  | FK lógico para `files` (entrada do CSV — #4)                 |
| `embedding`        | vector(1536) (pgsql); json (sqlite) | gerado na ingestão por #07 via `EmbeddingClient`            |
| `created_at`       | timestamp                     |                                                              |
| `updated_at`       | timestamp                     |                                                              |

Índices:
- composto `(workspace_id, created_at)` — listagem
- partial `(workspace_id, created_at) WHERE roadmap_item_id IS NULL` — só Postgres, alimenta o filtro `?unclustered=true`
- `ivfflat (embedding vector_cosine_ops) WITH (lists=100)` — só Postgres, alimenta busca semântica do pré-cluster

FK real (#07): `ideas.roadmap_item_id` → `roadmap_items.id` ON DELETE SET NULL (pgsql).

## Invariantes

- `workspace_id` **sempre** vem do claim JWT (`request()->workspace_id()`). Nunca aceitar no body/URL.
- `Idea` **não** tem `workspace()` belongsTo — workspaces são externas.
- `description` obrigatória; `title` é opcional. Quando `title` é null, o front exibe `Str::limit(description, 60)` como fallback.
