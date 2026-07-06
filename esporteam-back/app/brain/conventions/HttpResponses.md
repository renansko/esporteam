# HTTP responses (envelope + paginação)

## Envelope padrão

Toda resposta da API (sucesso ou erro) viaja no shape:

```json
{ "success": true,  "message": "...", "data": ... }
{ "success": false, "message": "...", "errors": { ... } }
```

Implementação em `App\Traits\ApiResponse`. **Controllers nunca chamam `->toArray()` direto no Model** — usar sempre um `Resource` (ver [resources/](../resources/)).

`AppServiceProvider::boot()` chama `JsonResource::withoutWrapping();` pra evitar `data.data.foo` quando o controller já envolve com `successResponse($resource)`.

## Helpers do trait

- `successResponse($data, $message, 200)` — sucesso genérico
- `createdResponse($data, $message)` — 201
- `errorResponse($message, $errors, $statusCode)` — erro arbitrário
- `deletedResponse()` — 204 sem body
- `paginatedResponse($collection, $message)` — extrai `data`/`links`/`meta` de `LengthAwarePaginator` ou `AnonymousResourceCollection` paginada

## Paginação

`?per_page=N&page=N` (page-based, default 50).
Shape `{ data: [...], links: {first,last,prev,next}, meta: {current_page,from,to,total,...} }` é o do Laravel — não inventar variações.

### Cursor-based (listings reordenáveis)

Endpoints onde a ordem da listagem pode mudar entre páginas (`GET /api/roadmap` por `score DESC`, runs por `started_at DESC`, decisions de uma run) usam **cursor pagination** (`cursorPaginate(50)`) — query `?cursor=...`. Shape similar, sem `meta.current_page` / `meta.total`.

## Códigos de erro

Renderização vive em `bootstrap/app.php` via `withExceptions(...)`:

| Exception                     | Status | Envelope                                                  |
|-------------------------------|--------|-----------------------------------------------------------|
| `ValidationException`         | 422    | `{ success:false, message, errors: { campo: [..] } }`      |
| `AuthenticationException`     | 401    | `{ success:false, message }`                              |
| `AuthorizationException`      | 403    | `{ success:false, message }`                              |
| `NotFoundHttpException`       | 404    | `{ success:false, message: "Resource not found." }`        |

Outras exceções não são renderizadas no envelope — devem ser tratadas no controller (`errorResponse(...)`) ou subirem como 500 pra observabilidade.

## Datetime

Sempre **ISO 8601** nos Resources (`->toISOString()` no campo). Nada de `created_at` cru de Eloquent.
