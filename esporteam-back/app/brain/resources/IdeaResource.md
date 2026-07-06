# IdeaResource

Serialização HTTP de uma `Idea`. Único shape exposto pelos endpoints `/api/ideas*`.

## Shape

```json
{
  "id": 7,
  "source": "manual",
  "title": null,
  "description": "Quero exportar relatórios em PDF",
  "author_email": "pm@acme.test",
  "created_at": "2026-05-20T18:42:11.000000Z",
  "clustered": false,
  "roadmap_item_id": null
}
```

| Campo             | Origem                                              | Notas                               |
|-------------------|-----------------------------------------------------|-------------------------------------|
| `id`              | `ideas.id`                                          |                                     |
| `source`          | `IdeaSource::value` (string)                        | nunca o enum cru                    |
| `title`           | `ideas.title`                                       | pode ser `null`                     |
| `description`     | `ideas.description`                                 |                                     |
| `author_email`    | `ideas.author_email`                                | já normalizado pelo mutator         |
| `created_at`      | `created_at->toISOString()`                         | ISO 8601                            |
| `clustered`       | `roadmap_item_id !== null` (bool computado)         | atalho útil pra UI                  |
| `roadmap_item_id` | `ideas.roadmap_item_id`                             | `null` se ainda não clusterizada    |

## Não inclui

- `workspace_id` — desnecessário no contexto da resposta autenticada (cliente já sabe seu workspace).
- `updated_at` / `source_file_id` — internos.

## Coleções

`IdeaResource::collection($paginator)` é embrulhada pelo `paginatedResponse()` (ver [conventions/HttpResponses.md](../conventions/HttpResponses.md)) — shape final:

```json
{ "success": true, "message": "Success", "data": [...], "links": {...}, "meta": {...} }
```
