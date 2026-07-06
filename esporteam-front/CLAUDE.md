# esporteam-front

## Referencias do workspace

Antes de alterar codigo neste app, leia `../CONTEXT.md` para linguagem de produto e `../CODEBASE-DESIGN.md` para desenho de modulos. A UI deve usar a mesma linguagem do dominio: Perfil Esportivo, Entusiasta, Professor, Aluno, Modalidade, Conexao, Grupo Esportivo, Descoberta e Disponibilidade.

## Estrutura

Organize comportamento por feature quando ele pertence a um fluxo de produto, e use `src/composables/` para interfaces pequenas e reutilizaveis. Componentes devem renderizar estado e emitir intencoes; regras de fluxo, chamadas HTTP, normalizacao de payloads e decisoes de estado devem ficar em composables, stores ou modulos de feature.

Ao consumir o backend, preserve a distincao entre `User` e Perfil Esportivo: autenticacao usa usuario; descoberta e participacao usam perfil esportivo.
