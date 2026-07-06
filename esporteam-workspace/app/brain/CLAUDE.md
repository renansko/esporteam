# Brain — Schema de Documentação

Este diretório é uma wiki mantida pelo LLM para o projeto **esporteam-workspace**. O LLM escreve e mantém tudo aqui; você lê e navega.

## Estrutura

```
app/brain/
├── CLAUDE.md          ← schema e regras (este arquivo)
├── index.md           ← índice de tudo na wiki
├── log.md             ← log cronológico de operações
├── entities/          ← páginas de Models/Entidades
├── services/          ← páginas de Services (resumo + índice de funções)
└── functions/         ← índice de funções por Service
```

## Camadas

### `entities/{EntityName}.md`
- Campos, tipos, casts e fillable
- Relacionamentos (`hasMany`, `belongsTo`, etc.)
- Quais Services operam sobre essa entidade
- Quais rotas a expõem

### `services/{ServiceName}.md`
- Resumo do propósito do Service (3-5 linhas)
- Dependências injetadas (outros Services, Models, Clients)
- Índice linkado de funções → aponta para `functions/{ServiceName}.md`

### `functions/{ServiceName}.md`
- Uma seção `##` por método público
- Assinatura do método
- O que faz (2-3 linhas)
- Parâmetros e retorno
- Side effects (gravações, eventos disparados, chamadas externas)
- Entidades tocadas

## Operações

### Ingest
Gatilhos:
- Pedido em prompt: "ingest WorkspaceService", "atualiza o brain do Workspace", etc.

Fluxo para um **Service**:
1. Lê `app/Services/{ServiceName}.php`
2. Atualiza `services/{ServiceName}.md`
3. Atualiza `functions/{ServiceName}.md`
4. Atualiza entidades tocadas pelo Service
5. Atualiza `index.md`
6. Adiciona entrada em `log.md`

Fluxo para uma **Entity**:
1. Lê `app/Models/{EntityName}.php`
2. Atualiza `entities/{EntityName}.md`
3. Atualiza `index.md`
4. Adiciona entrada em `log.md`

## Convenções

- Nomes de arquivo: PascalCase (`WorkspaceService.md`, `Workspace.md`)
- Links internos: `[[entities/Workspace]]`, `[[services/WorkspaceService]]`, `[[functions/WorkspaceService]]`
- Log: cada entrada começa com `## [YYYY-MM-DD] {operação} | {nome}`

## Anotação `@wiki` no código PHP

Toda função pública relevante de Service e todo Model ingerido deve ter no docblock uma linha apontando para a página de wiki correspondente. Isso dá ao LLM um atalho direto do código para o brain, mas código ainda não ingerido pode não ter essa anotação.

Formato:

```php
/**
 * @wiki app/brain/functions/WorkspaceService.md#create
 */
public function create(array $data): Workspace
```

Para Models / Entities, no docblock da classe:

```php
/**
 * @wiki app/brain/entities/Workspace.md
 */
class Workspace extends Model
```

Para Services, no docblock da classe:

```php
/**
 * @wiki app/brain/services/WorkspaceService.md
 */
class WorkspaceService
```

Regras:
- Path sempre relativo à raiz do projeto (`app/brain/...`)
- Em funções, usar fragmento `#nomeDoMetodo` apontando para a seção dentro do arquivo de functions
- O ingest deve manter essas anotações sincronizadas com o brain para os arquivos que ele atualizar
