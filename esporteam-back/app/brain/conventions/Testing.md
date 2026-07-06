# Testing

## Stack

- **Pest 4.x** + **pest-plugin-laravel 4.x**, PHPUnit 12 por baixo.
- `tests/bootstrap.php` define `APP_RUNNING_TESTS = true` antes do autoload — flag lida pelo `AuthenticateViaAuthService` pra bypass de JWT em testes.

## TDD obrigatório

**Nenhuma linha de produção sem teste falhando primeiro.** Ciclo red → green → refactor. Listas de aceitação dão o mínimo; testes adicionais nascem do ciclo.

## Naming

- `tests/Feature/{Module}/*.php` — HTTP + banco real (SQLite in-memory)
- `tests/Unit/Services/*.php` — pure logic em services/DTOs
- Arquivos terminam em `Test.php` ou usam apenas `it(...)` / `test(...)` do Pest sem class

## Helper `actingAsWorkspace`

Em `tests/Pest.php`:

```php
actingAsWorkspace(42)
    ->postJson('/api/ideas', [...])
    ->assertCreated();

actingAsWorkspace(7, ['id' => 99, 'permissions' => 1])
    ->getJson('/api/ideas');
```

Bastidores: monta os headers `X-Test-Workspace`, `X-Test-User-Id`, `X-Test-Permissions` que o middleware lê no caminho de bypass (`APP_RUNNING_TESTS`). Resultado: `$request->user()->workspace_id == 42` dentro do controller.

## Cross-tenant — não-negociável

Toda listagem que filtra por workspace **precisa** de um teste de isolamento: cria 2 workspaces, N entidades em cada, garante que a response do workspace A não vaza nada do B. Esse padrão protege a demo contra leak.

## Asserts sobre envelope

```php
$response->assertJson([
    'success' => true,
    'data'    => ['source' => 'manual', ...],
]);
```

Pra 422: `->assertStatus(422)->assertJsonStructure(['success','message','errors' => ['description']])`.

## Banco

- Feature tests: `use RefreshDatabase` (vem do `Tests\Feature\TestCase`); SQLite `:memory:` via `phpunit.xml`.
- Migrations rodam em cada arquivo; PostgreSQL-only features (partial indexes via `DB::statement`) ficam em `if ($connection->getDriverName() === 'pgsql')` pra não quebrar SQLite.
