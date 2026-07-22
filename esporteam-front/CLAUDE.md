# esporteam-front

## Referencias do workspace

Antes de alterar codigo neste app, leia `../CONTEXT.md` para linguagem de produto e `../CODEBASE-DESIGN.md` para desenho de modulos. A UI deve usar a mesma linguagem do dominio: Perfil Esportivo, Entusiasta, Professor, Aluno, Modalidade, Conexao, Grupo Esportivo, Descoberta e Disponibilidade.

## Estrutura

Organize comportamento por feature quando ele pertence a um fluxo de produto, e use `src/composables/` para interfaces pequenas e reutilizaveis. Componentes devem renderizar estado e emitir intencoes; regras de fluxo, chamadas HTTP, normalizacao de payloads e decisoes de estado devem ficar em composables, stores ou modulos de feature.

Ao consumir o backend, preserve a distincao entre `User` e Perfil Esportivo: autenticacao usa usuario; descoberta e participacao usam perfil esportivo.

## Direcao visual: liquid glass

A interface do `esporteam-front` deve seguir a linguagem visual liquid glass da Cola Aí. Novas superficies elevadas devem reutilizar os tokens `--glass-*` e as classes compartilhadas de `src/style.css` (`glass-surface`, `glass-surface-strong` e `glass-pill`) em vez de criar valores isolados de blur, transparencia, borda ou sombra.

O vidro deve comunicar hierarquia, nao decorar tudo. Use somente estes tres niveis: `glass-surface` para paineis e cartoes, `glass-surface-strong` para barras fixas e navegacao, e `glass-pill` para controles compactos. Mantenha conteudo principal com contraste legivel e use o azul e o lime da marca como acentos. Toda superficie translucida precisa ter fundo opaco de fallback, borda clara, suporte a tema escuro e comportamento sem blur para `prefers-reduced-transparency`. Nao aplique blur a itens repetidos de listas rolaveis; aplique-o ao contêiner fixo que estabelece a hierarquia.

## Runtime local

O frontend deste repo roda normalmente pelo Docker Compose em `../esporteam-docker/docker-compose.yml`. O container `esporteam-front` publica o Vite em `http://127.0.0.1:5173`.

Antes de iniciar servidor local, verifique se o compose ja esta rodando. Se `5173` estiver ocupado, trate isso como sinal esperado de que o app Docker ja esta ativo; nao suba outro `npm run dev` no host em `5174` ou porta alternativa para revisar a UI. Use `http://127.0.0.1:5173` e, quando precisar executar comandos no ambiente do app, prefira o servico `esporteam-front` via Docker Compose.
