# IdeaIngestionService

Ponto único de entrada para criar uma `Idea` no workspace. Toda fonte (#3 manual, #4 CSV, #5 público, #10 gap de concorrente) passa por aqui.

## Responsabilidades

- Persistir uma `Idea` a partir do DTO `IngestIdeaInput`.
- Aplicar invariantes via Model (e.g. normalização de `author_email` no mutator).
- Ser o lugar onde **dedupe por hash** entra (planejado para #4 CSV).

## Não responsabilidades

- **Não valida HTTP** — quem chama é responsável (FormRequest no controller, parser no #4).
- **Não decide `workspace_id`** — recebe pronto. Quem decide é o controller, lendo do claim JWT.
- **Não fala com esporteam-workspace** — esse trabalho é do `WorkspaceClient`.

## Interface

```php
$service->ingest(new IngestIdeaInput(
    workspaceId: 42,
    source: IdeaSource::Manual,
    description: 'Texto cru',
    title: null,
    authorEmail: 'foo@bar.com',
    sourceFileId: null,
)): Idea
```

## Quando estender

- **Dedupe (#4)**: adicionar campo `contentHash` no DTO + check antes do `save()`. Retornar a Ideia existente quando bate.
- **Ingestão pública (#5)**: criar uma variante do DTO que recebe `slug` em vez de `workspaceId` (resolução acontece no controller público).

## Embedding (#7)

Após salvar, o service chama `attachEmbedding($idea)` (best-effort): obtém o vetor via `EmbeddingClient` e grava em `ideas.embedding`. Falhas são logadas no canal `clustering` e silenciadas — Idea sem embedding cai como singleton no pré-cluster.

Backfill em lote: `php artisan ideas:backfill-embeddings [--workspace=<id>] [--batch=200]`.
