# esporteam-back

## Referencias do workspace

Antes de alterar codigo neste servico, leia `../CONTEXT.md` para linguagem de dominio e `../CODEBASE-DESIGN.md` para desenho de modulos. Este servico e o dono de descoberta esportiva e participacao: Perfil Esportivo, Modalidade, Professor, Aluno, Conexao, Grupo Esportivo e Disponibilidade.

## Brain / Wiki

Este projeto mantém uma wiki LLM-maintained em `app/brain/` que documenta entidades, services e funções. **Antes de explorar o código com grep/find, consulte primeiro o brain** — é mais barato em tokens quando a página existe, mas confirme no código quando o brain estiver incompleto ou antigo.

- Schema completo: `app/brain/CLAUDE.md`
- Catálogo: `app/brain/index.md`
- Páginas: `app/brain/entities/`, `app/brain/services/`, `app/brain/functions/`

Quando existir anotação `@wiki` no docblock, use-a como ponto de entrada direto para o brain antes de mexer no código. Nem todo arquivo do domínio esportivo já foi ingerido; se a anotação não existir, consulte `app/brain/index.md` e atualize o brain quando a mudança alterar comportamento relevante.

Quando o usuário pedir "ingest {nome}" ou "atualiza o brain do {nome}", siga o fluxo definido em `app/brain/CLAUDE.md`.

## Bruno (API client)

A collection de requests da API vive em `bruno/` e é versionada no git. Detalhes completos em `bruno/README.md`.

- Abrir no app desktop do [Bruno](https://www.usebruno.com/): *Open Collection* → pasta `bruno/`.
- Selecionar o environment **local** e colar o JWT em `vars:secret → jwtToken` (obtido via login no `esporteam-auth`).
- URLs sempre via `{{baseUrl}}` — nunca hardcode host/porta.
- Um arquivo `.bru` por endpoint, agrupado por recurso (`ideas/`, `me/`, ...).
- **Ao adicionar ou alterar endpoint em `routes/api.php`, atualizar a collection no mesmo PR.** 
