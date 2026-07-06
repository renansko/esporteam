# esporteam-auth

## Referencias do workspace

Antes de alterar codigo neste servico, leia `../CONTEXT.md` para linguagem de dominio compartilhada e `../CODEBASE-DESIGN.md` para desenho de modulos. Este servico e dono de identidade de autenticacao, usuarios, permissoes globais, JWT, 2FA, impersonacao e auditoria; ele nao e dono da identidade social de descoberta esportiva.

## Brain / Wiki

Este projeto mantém uma wiki LLM-maintained em `app/brain/` que documenta entidades, services e funções. **Antes de explorar o código com grep/find, consulte primeiro o brain** — é mais barato em tokens quando a página existe, mas confirme no código quando o brain estiver incompleto ou antigo.

- Schema completo: `app/brain/CLAUDE.md`
- Catálogo: `app/brain/index.md`
- Páginas: `app/brain/entities/`, `app/brain/services/`, `app/brain/functions/`

Quando existir anotação `@wiki` no docblock, use-a como ponto de entrada direto para o brain antes de mexer no código. Nem todo arquivo deste serviço já foi ingerido; se a anotação não existir, consulte `app/brain/index.md` e atualize o brain quando a mudança alterar comportamento relevante.

Quando o usuário pedir "ingest {nome}" ou "atualiza o brain do {nome}", siga o fluxo definido em `app/brain/CLAUDE.md`.

## Responsabilidade

Microsserviço responsável pela autenticação, gestão de usuários e emissão de tokens JWT (RS256) da plataforma esporteam. Também expõe endpoints administrativos restritos a super admins esporteam.

## Stack

- Laravel 13 + PHP 8.3
- PostgreSQL 16
- Redis 7
- JWT RS256 (firebase/php-jwt)

## Bitmask de Permissões Globais (coluna `users.permissions`)

A coluna `permissions` é um bitmask inteiro não-negativo. Bits definidos:

| Bit | Valor | Flag                  | Descrição                                                                |
|-----|-------|-----------------------|--------------------------------------------------------------------------|
| 0   | 1     | `can_create_workspace`| Usuário pode criar novos workspaces                                      |
| 1   | 2     | `is_esporteam_admin`       | Super administrador da plataforma esporteam                                   |
| 2   | 4     | `is_esporteam_owner`       | Dono da plataforma esporteam — pode editar permissões de qualquer usuário     |

Exemplos:
- `0`  → sem permissões globais
- `1`  → pode criar workspace
- `2`  → admin esporteam (sem poder criar workspace via flag própria; admins têm bypass global)
- `3`  → admin esporteam + pode criar workspace
- `7`  → owner + admin + pode criar workspace (valor padrão usado pelo bootstrap `esporteam:grant-owner`)

Convenção: owner implica admin. Ao promover alguém a owner via `esporteam:grant-owner`, setamos também os bits 0 e 1 (`permissions |= 7`). Owners NÃO perdem nada de admin — apenas ganham a capacidade exclusiva de editar permissões de terceiros.

O JWT padrão inclui os campos `permissions` (int), `is_esporteam_admin` (bool, derivado de `(permissions & 2) === 2`) e `is_esporteam_owner` (bool, derivado de `(permissions & 4) === 4`). Tokens de impersonação sempre têm `is_esporteam_admin = false` e `is_esporteam_owner = false`.

## Contratos de Comunicação

### Endpoints Públicos

- `GET  /api/health`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/password/forgot`
- `POST /api/auth/password/reset`

### Endpoints Autenticados (`auth.jwt`)

- `GET   /api/me`
- `PATCH /api/me` — self-service profile update (`name`, `email`). Email único validado no FormRequest. Senha não é editável aqui (use forgot/reset).
- `DELETE /api/me` — self-service account deletion (soft delete + anonimização)
- `POST  /api/workspace/select` — emite token com contexto de workspace
- `PUT   /api/users/{id}/permissions` — self-service (usuário só pode atualizar as próprias permissões)

### Endpoints Service-to-Service (`service.token`)

- `GET  /api/service/users/by-email?email=...`
- `POST /api/service/users/bulk-lookup` — retorna dados (id, name, email) de múltiplos usuários. Body:
  ```json
  { "ids": [1, 2, 3] }
  ```
  Validação: `ids` obrigatório, array, min 1, max 100 items, cada item inteiro `> 0`. Ids inexistentes são omitidos silenciosamente do array `data`. Resposta:
  ```json
  {
    "success": true,
    "message": "Users retrieved successfully",
    "data": [ { "id": 1, "name": "João", "email": "joao@example.com" } ]
  }
  ```
- `POST /api/service/audit` — permite que outros microsserviços gravem entradas no audit log central. Body:
  ```json
  {
    "admin_user_id": 1,
    "admin_email": "admin@esporteam.com",
    "action": "update_student",
    "target_type": "student",
    "target_id": 42,
    "target_snapshot": { "...": "..." },
    "metadata": { "...": "..." }
  }
  ```
- `POST /api/service/users/grant-permissions` — concede um bitmask de permissions a um usuário existente sem requerer um JWT de owner. Body:
  ```json
  { "user_id": 42, "permissions": 2 }
  ```
  Validação: `user_id` obrigatório (int >= 1), `permissions` obrigatório (int >= 0). O bit 2 (`is_esporteam_owner`, valor 4) é rejeitado com 422 — owner continua sendo bootstrap exclusivo via `esporteam:grant-owner`. Cada chamada grava uma entrada no audit log com `admin_user_id=0`, `admin_email='service'`, `action='grant_permissions_via_service'`. Uso primário: bootstrap de admin para a suíte E2E (`esporteam-school-front/e2e/helpers/api.ts → registerAdminWithWorkspace`).

### Endpoints de Super Admin (`auth.jwt` + `esporteam.admin`)

Todos os endpoints abaixo exigem que o JWT tenha `(permissions & 2) === 2`. Caso contrário, retornam `403` com mensagem `Acesso restrito a administradores esporteam`.

- `POST /api/admin/impersonate`
  - Body: `{ "user_id": int, "workspace_id": int|null }`
  - Gera um JWT curto (1h, hard-coded) para o usuário alvo. O token NÃO herda flag de admin.
  - Payload inclui `impersonated_by` (id do admin) e `impersonated_at` (timestamp).
  - É proibido impersonar outro admin esporteam (retorna 422).
  - Resposta:
    ```json
    {
      "success": true,
      "message": "Token de impersonação criado com sucesso",
      "data": {
        "token": "...",
        "expires_at": "2026-04-10T12:00:00+00:00",
        "user": { "id": 1, "name": "...", "email": "..." }
      }
    }
    ```

- `GET  /api/admin/users?email=&name=&is_admin=&per_page=25`
  - Lista paginada de usuários. `per_page` é limitado a 100.
  - Filtros: `email` (like), `name` (like), `is_admin` (bitmask `(permissions & 2) = 2`).

- `GET  /api/admin/users/{id}` — detalhes de um usuário.

- `GET  /api/admin/audit?action=&admin_user_id=&admin_email=&target_type=&target_id=&from=&to=&per_page=25`
  - Lista paginada de entradas do audit log. `per_page` é limitado a 100.
  - Filtros: `action` (exato), `admin_user_id` (int), `admin_email` (like), `target_type` (exato), `target_id` (int), `from` / `to` (datetime, janela sobre `created_at`).
  - Ordenação fixa: `created_at DESC` (mais recentes primeiro).

### Endpoint restrito ao dono da plataforma (`auth.jwt` + `esporteam.owner`)

- `PUT  /api/admin/users/{id}/permissions`
  - **Restrito a donos da plataforma esporteam (bit 2 do bitmask global, valor 4).** Admins comuns recebem 403.
  - Body: `{ "permissions": int }`
  - Atualiza o bitmask de qualquer usuário. Grava entrada no audit log com `action = update_permissions` e `metadata = { old, new }`.
  - NÃO substitui a rota self-service `/api/users/{id}/permissions`, que continua ativa para usuários comuns atualizarem as próprias permissões (com os bits 1 e 2 preservados da DB — anti-escalation).
  - Nota: Editar permissões é restrito aos donos da plataforma (bit 2). Admins comuns perdem essa capacidade, mas mantêm listagem de usuários, audit log, impersonation e gestão de workspaces.

## Padrão Controller → Service

Conforme CLAUDE.md raiz, controllers NÃO contêm lógica de negócio. Novos domínios devem criar um `Service` em `app/Services/` e o controller apenas orquestra request/response.

## Audit Log

Tabela: `admin_audit_logs` (sem `updated_at`).

Colunas: `id`, `admin_user_id`, `admin_email`, `action`, `target_type`, `target_id`, `target_snapshot` (json), `metadata` (json), `ip_address`, `created_at`.

Model: `App\Models\AdminAuditLog` (`$guarded = []`, `$timestamps = false`).

Service: `App\Services\AuditLogService::log(int $adminId, string $adminEmail, string $action, string $targetType, int $targetId, array $snapshot = [], array $metadata = []): void`.

Regras:
- O service nunca lança exceção. Falhas são gravadas via `Log::error()` para não bloquear a operação principal.
- `ip_address` é capturado automaticamente via `request()->ip()`.
- Outros microsserviços devem usar o endpoint `POST /api/service/audit` (autenticado via `service.token`) para escrever no log central.

## JwtService

- `encode(array $user, ?string $workspaceId = null, ?array $schoolPermissions = null, bool $isWorkspaceOwner = false): string`
  - Token padrão (TTL = `config('jwt.ttl')`).
- `encodeImpersonation(array $targetUser, int $adminId, ?int $workspaceId = null, ?bool $isWorkspaceOwner = null, ?array $schoolPermissions = null): string`
  - Token de impersonação. TTL hard-coded em 3600s.
  - `is_esporteam_admin` sempre `false`.
  - Inclui `impersonated_by` e `impersonated_at`.
- `decode(string $token): object`

## Middlewares

- `auth.jwt` → `App\Http\Middleware\AuthenticateJwt`
- `service.token` → `App\Http\Middleware\ServiceTokenMiddleware`
- `esporteam.admin` → `App\Http\Middleware\RequireEsporteamAdmin` (403 se `(permissions & 2) !== 2`)
- `esporteam.owner` → `App\Http\Middleware\RequireEsporteamOwner` (403 se `(permissions & 4) !== 4`)

## Comandos Artisan

- `esporteam:grant-owner {email}` — Concede o role de dono da plataforma esporteam ao usuário informado. Executa `permissions |= 7` (owner + admin + can_create_workspace), grava entrada no audit log com `action = grant_esporteam_owner`, `admin_user_id = 0`, `admin_email = 'console'`. Usado para bootstrap do primeiro owner da plataforma. Retorna `FAILURE` se o email não existe.

## 2FA (Autenticação de Dois Fatores)

Base implementada com TOTP (Google Authenticator, Authy, etc.) via `pragmarx/google2fa`.

Colunas em `users`: `two_factor_secret` (encrypted), `two_factor_recovery_codes` (encrypted:array), `two_factor_confirmed_at` (datetime).

Service: `App\Services\TwoFactorService` — enable, confirm, disable, verifyCode, verifyRecoveryCode, isEnabled.

Endpoints (middleware `auth.jwt`):
- `GET    /api/2fa/status` — retorna `{ enabled: bool }`
- `POST   /api/2fa/enable` — gera secret + QR code URL + recovery codes
- `POST   /api/2fa/confirm` — confirma com código TOTP (size:6)
- `DELETE  /api/2fa/disable` — desativa (requer código TOTP ou recovery code)

Fluxo de login com 2FA:
1. `POST /api/auth/login` com email + password
2. Se 2FA ativo, retorna `{ two_factor_required: true }` (sem token)
3. Cliente reenvia login com `two_factor_code` adicional
4. Recovery codes também aceitos no campo `two_factor_code`

## Relatório de Alterações

- `[2026-05-06]` — `WorkspaceTokenController::select` deixa de embutir `school_permissions` no JWT (passa `null`). As permissões do school agora são servidas em tempo real pelo `esporteam-school` via `GET /api/me/permissions`. Mudança motivada por bug em que alterar role de um staff não refletia até o token expirar (TTL=1 ano). O JWT continua com identidade + flags globais (`is_workspace_owner`, `is_esporteam_admin`); só a parte volátil de permissões saiu. `JwtService::encode` mantém o parâmetro opcional pra compatibilidade — chamadas que precisem injetar (ex: testes, impersonação) continuam funcionando.
- `[2026-05-05]` — Adicionado `PATCH /api/me` para self-service profile update (name + email). FormRequest `UpdateMeRequest` valida unicidade do email com `ignore($userId)`. Service: `UserService::updateMe(User $user, array $data): User` — só aceita campos `name` e `email` (filtra resto). Controller: `UserController::updateMe`. Frontend (`esporteam-school-front`) consome via `useAuth.updateMe()` na tela `/profile`.
- `[2026-04-28]` — Testes Pest para `DELETE /api/me`: `tests/Unit/Services/UserServiceSoftDeleteTest.php` (9 testes: anonimização, soft delete, RabbitMQ mock, idempotência, owner) e `tests/Feature/Auth/DeleteMeApiTest.php` (8 testes: happy path, Content-Type, withTrashed, login pós-delete). Suite: 107 testes / 300 assertions.
- `[2026-04-28]` — Adicionado `POST /api/service/users/grant-permissions` (S2S, middleware `service.token`) para promover usuários a `esporteam_admin` sem requerer um JWT de owner. Bit 2 (owner) é rejeitado com 422 — bootstrap de owner continua exclusivo via `esporteam:grant-owner`. Service: `AdminUserService::grantPermissionsViaService(int $targetUserId, int $permissions): User` — loga em audit log com `admin_user_id=0, admin_email='service', action='grant_permissions_via_service'`. Uso primário: bootstrap de admin pra suíte E2E do `esporteam-school-front` (fixture `adminSession`).
- `[2026-04-13]` — Base do 2FA: migration (colunas two_factor_* em users), TwoFactorService, TwoFactorController, endpoints /api/2fa/*, fluxo de login com verificação TOTP. Complexidade de senha (Password::min(8)->mixedCase()->numbers()) no RegisterRequest e ResetPasswordRequest. CORS configurável via env CORS_ALLOWED_ORIGINS. Traduções pt_BR de validação de senha.
- `[2026-04-11]` — Novo role "esporteam owner" (bit 2 do bitmask global, valor 4). Middleware `RequireEsporteamOwner` protege a edição de permissões (`PUT /api/admin/users/{id}/permissions`). Self-service preserva bits 1 e 2 (anti-escalation). Impersonation bloqueia admins e owners. JWT inclui claim `is_esporteam_owner`. Comando artisan `esporteam:grant-owner` para bootstrap.
- `[2026-04-11]` — Adicionado `POST /api/service/users/bulk-lookup` para enriquecer dados de usuários em chamadas service-to-service (consumido pelo esporteam-workspace na listagem de membros do admin).
- `[2026-04-10]` — Adicionado `GET /api/admin/audit` para consulta de audit logs com filtros (`action`, `admin_user_id`, `admin_email`, `target_type`, `target_id`, `from`, `to`, `per_page`), seguindo o padrão Controller → Service (`AdminAuditController` + `AdminAuditService` + `ListAuditLogsRequest`).
- `[2026-04-10]` — Adicionadas features de super admin: middleware `RequireEsporteamAdmin`, audit log (`admin_audit_logs` + `AuditLogService` + endpoint service-to-service `POST /api/service/audit`), impersonation (`ImpersonationService`, `JwtService::encodeImpersonation`, `POST /api/admin/impersonate`), gestão de usuários admin (`AdminUserService`, `GET /api/admin/users`, `GET /api/admin/users/{id}`, `PUT /api/admin/users/{id}/permissions`). Documentado o bitmask global de permissões.
