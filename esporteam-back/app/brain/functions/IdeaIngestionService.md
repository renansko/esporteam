# IdeaIngestionService — funções públicas

## `ingest(IngestIdeaInput $input): Idea`

Cria e persiste uma `Idea`. Retorna o model recém-salvo (com `id` e `created_at` preenchidos).

### Input — `IngestIdeaInput` (DTO imutável)

| Campo            | Tipo                | Obrigatório | Notas                                                  |
|------------------|---------------------|-------------|--------------------------------------------------------|
| `workspaceId`    | int                 | sim         | claim JWT — quem chama lê de `request()->workspace_id()` |
| `source`         | `IdeaSource`        | sim         | enum, não string                                       |
| `description`    | string              | sim         | max 5000 chars (validado no FormRequest do controller) |
| `title`          | ?string             | não         | max 255                                                |
| `authorEmail`    | ?string             | não         | normalizado para lowercase + trim pelo mutator         |
| `sourceFileId`   | ?int                | não         | FK lógico para `files` (CSV — #4)                      |

### Side effects

- Insere uma linha em `ideas`.
- Aciona mutators do `Idea` (e.g. lowercase do email).

### Falhas previsíveis

- Driver de DB indisponível → exception do PDO (não tratada aqui).
- `description` vazia chega como string vazia ao DB; o FormRequest no controller é quem rejeita.

### Não retorna eventos / não dispara fila

A próxima fatia (#7 clustering) consulta `ideas WHERE roadmap_item_id IS NULL` por demanda — não há push.
