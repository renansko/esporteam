# Brain do Esporteam — Schema de Documentação

Wiki mantida pelo LLM para o projeto **esporteam-back**. O LLM escreve e mantém tudo aqui; você lê e navega.

Cada página existe pra dar contexto que **não dá pra inferir só lendo o código** (decisões, invariantes, atalhos mentais).

## Regras

- **Todo `.md` < 100 linhas.** Se passar disso, fatie em sub-páginas e referencie pelo `index.md`.
- Arquivos PHP de domínio ingeridos devem ter um docblock `@wiki app/brain/{camada}/{Nome}.md` apontando pra sua página. Código ainda não ingerido pode não ter essa anotação.
- Mudanças não-triviais deixam uma linha em `log.md` (data, escopo, ID da issue).

## Estrutura

```
app/brain/
├── CLAUDE.md          ← schema e regras (este arquivo)
├── index.md           ← índice de tudo na wiki
├── log.md             ← log cronológico de operações
├── entities/          ← Models e suas invariantes
├── services/          ← Application services / casos de uso
├── functions/         ← Funções públicas com contrato detalhado
├── resources/         ← Shapes de serialização HTTP
└── conventions/       ← Padrões transversais do projeto
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

### `resources/{ResourceName}.md`
- Shape JSON de serialização HTTP (campos retornados, formato)

### `conventions/{Topic}.md`
- Padrões transversais (envelopes de resposta, testes, etc.)

## Como navegar

Comece pelo [`index.md`](index.md). Use grep por `@wiki app/brain/...` no código pra ir do PHP pra wiki.

## Operações

### Ingest
Gatilhos:
- Pedido em prompt: "ingest {ServiceName}", "atualiza o brain do {Entity}", etc.

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

- Nomes de arquivo: PascalCase (`UserService.md`, `User.md`)
- Links internos: `[[entities/User]]`, `[[services/UserService]]`, `[[functions/UserService]]`
- Log: tabela `| Data | Issue | Escopo |`

## Anotação `@wiki` no código PHP

Toda função pública relevante de Service e todo Model ingerido deve ter no docblock uma linha apontando para a página de wiki correspondente. Isso dá ao LLM um atalho direto do código para o brain, mas o código esportivo mais novo ainda pode precisar de ingest.

Formato em método:

```php
/**
 * @wiki app/brain/functions/{ServiceName}.md#nomeDoMetodo
 */
public function nomeDoMetodo(...) {}
```

Em Model / Entity (docblock da classe):

```php
/**
 * @wiki app/brain/entities/{EntityName}.md
 */
class EntityName extends Model
```

Em Service (docblock da classe):

```php
/**
 * @wiki app/brain/services/{ServiceName}.md
 */
class ServiceName
```

Regras:
- Path sempre relativo à raiz do projeto (`app/brain/...`)
- Em funções, usar fragmento `#nomeDoMetodo` apontando para a seção dentro do arquivo de functions
- O ingest mantém essas anotações sincronizadas com o brain
