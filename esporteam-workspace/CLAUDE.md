# esporteam-workspace

## Referencias do workspace

Antes de alterar codigo neste servico, leia `../CONTEXT.md` para linguagem de dominio compartilhada e `../CODEBASE-DESIGN.md` para desenho de modulos. Este servico e dono de workspaces administrativos, membros, convites e estado do workspace; descoberta esportiva pertence ao `esporteam-back`.

## Brain / Wiki

Este projeto mantém uma wiki LLM-maintained em `app/brain/` que documenta entidades, services e funções. **Antes de explorar o código com grep/find, consulte primeiro o brain** — é mais barato em tokens quando a página existe, mas confirme no código quando o brain estiver incompleto ou antigo.

- Schema completo: `app/brain/CLAUDE.md`
- Catálogo: `app/brain/index.md`
- Páginas: `app/brain/entities/`, `app/brain/services/`, `app/brain/functions/`

Quando existir anotação `@wiki` no docblock, use-a como ponto de entrada direto para o brain antes de mexer no código. Nem todo arquivo deste serviço já foi ingerido; se a anotação não existir, consulte `app/brain/index.md` e atualize o brain quando a mudança alterar comportamento relevante.

Quando o usuário pedir "ingest {nome}" ou "atualiza o brain do {nome}", siga o fluxo definido em `app/brain/CLAUDE.md`.

## Responsabilidade

Microsserviço responsável pela gestão de workspaces, membros e convites da plataforma esporteam.

## Stack

- Laravel 13 + PHP 8.3
- PostgreSQL 16
- Autenticação via JWT propagado pelo `esporteam-auth` (middleware `auth.service`)

## Escopo

- CRUD de workspaces
- Gestão de membros (roles: owner, admin, member)
- Convites para ingresso em workspaces
- Dashboard administrativo para super admins (`is_esporteam_admin`)

## Autenticação / Autorização

- `auth.service` — valida o JWT RS256 emitido pelo `esporteam-auth` e hidrata `$request->user()` com `id`, `email`, `workspace_id`, `is_esporteam_admin`.
- `esporteam.admin` — exige `is_esporteam_admin === true`.
- `service.token` — valida o header `X-Service-Token` para chamadas service-to-service.
- `workspace.active` — bloqueia acesso a workspaces com `is_active = false`. Super admins fazem bypass automático. Se o workspace em contexto (rota `{workspace}` ou `user.workspace_id`) estiver desativado, retorna 403 com `error_code: workspace_deactivated`.

## Rotas

### Públicas

- `GET /api/health`
- `GET /api/workspaces/{workspace}/public`

### Admin (`auth.service` + `esporteam.admin`)

- `GET /api/admin/workspaces` — lista paginada com filtros `name`, `slug`, `is_active`, `per_page` (default 25). Eager-loads owner member + `members_count`.
- `GET /api/admin/workspaces/{workspace}` — detalhes com `members_count`, `invites_count`, `owner`, `is_active`, `created_at`.
- `GET /api/admin/workspaces/{workspace}/members` — lista paginada de membros do workspace, enriquecida com `name`/`email` buscados no `esporteam-auth` via `AuthUserClient`. Query params: `page` (default 1), `per_page` (default 25, max 100), `role` (opcional: `owner|admin|member`). Response: `{ data: { items: [{ user_id, name, email, role, created_at }], meta: { current_page, per_page, total, last_page } } }`. Usuários não resolvidos pelo esporteam-auth são retornados com `name = "(desconhecido)"` e `email = ""`.
- `PATCH /api/admin/workspaces/{workspace}/status` — body `{ "active": bool }`. Ativa/desativa o workspace e registra audit log no `esporteam-auth`.

### Service (`service.token`)

- `POST /api/service/workspaces/{workspace}/invites`
- `POST /api/service/invites/{token}/accept`

### Usuário autenticado (`auth.service`)

- `GET /api/workspaces`
- `POST /api/workspaces`

Sob `workspace.active`:
- `GET|PUT|DELETE /api/workspaces/{workspace}`
- `GET|POST /api/workspaces/{workspace}/members`
- `PUT|DELETE /api/workspaces/{workspace}/members/{user}`
- `GET|POST /api/workspaces/{workspace}/invites`
- `DELETE /api/workspaces/{workspace}/invites/{invite}`

- `POST /api/invites/{token}/accept`

## Campo `is_active`

- Coluna booleana na tabela `workspaces` (default `true`).
- Controlado via `PATCH /api/admin/workspaces/{workspace}/status`.
- Enforced pelo middleware `workspace.active`.
- Super admins podem acessar workspaces desativados mesmo com o middleware ativo.

## Integração com esporteam-auth (AuditLogClient)

`App\Services\AuditLogClient::log()` envia entradas de auditoria para `POST {AUTH_SERVICE_URL}/api/service/audit` com header `X-Service-Token: {AUTH_SERVICE_TOKEN}`.

Payload:
```json
{
  "admin_user_id": 1,
  "admin_email": "admin@esporteam.com",
  "action": "deactivate_workspace",
  "target_type": "workspace",
  "target_id": 42,
  "snapshot": { "name": "...", "slug": "..." },
  "metadata": {}
}
```

**O client NUNCA lança exceção** — falhas são registradas via `Log::error()` e a operação de negócio segue normalmente.

Env vars necessárias: `AUTH_SERVICE_URL`, `AUTH_SERVICE_TOKEN`.

## Integração com esporteam-auth (AuthUserClient)

`App\Services\AuthUserClient::lookup(array $ids)` faz bulk lookup de usuários no `esporteam-auth` via `POST {AUTH_SERVICE_URL}/api/service/users/bulk-lookup` com header `X-Service-Token: {AUTH_SERVICE_TOKEN}`.

Payload: `{"ids": [1, 2, 3]}` (máx 100).

Response esperada: `{"success": true, "data": [{"id": int, "name": string, "email": string}, ...]}` — ids não encontrados são omitidos pelo esporteam-auth.

O retorno do método é um array associativo keyed por `id` para lookup O(1).

**Fail-safe:** o client NUNCA lança exceção. Em qualquer falha (HTTP não-2xx, timeout, exception), registra via `Log::error()` e retorna `[]`. Cabe ao chamador tratar ids ausentes (ex: `AdminWorkspaceMemberService` usa placeholder `"(desconhecido)"`).

## Arquitetura

Segue o padrão Controller -> Service da esporteam:

- Controllers: apenas recebem request, chamam service, retornam response.
- Services: `WorkspaceService`, `AdminWorkspaceService`, `AuditLogClient`, etc.
- Responses padronizadas via trait `App\Traits\ApiResponse`.

## Relatório de alterações

- [2026-04-10] — Dashboard admin de workspaces: CRUD via Admin/AdminWorkspaceController, campo is_active com bloqueio via middleware CheckWorkspaceActive, integração de audit log com esporteam-auth via AuditLogClient
- [2026-04-11] — Endpoint admin GET /api/admin/workspaces/{workspace}/members lista membros do workspace enriquecidos com nome/email via AuthUserClient (service-to-service com esporteam-auth).
