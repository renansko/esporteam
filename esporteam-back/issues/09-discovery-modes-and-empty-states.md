# 09 - Discovery Modes And Empty States

## What to build

Separar a descoberta em modos de `Pessoas`, `Sessoes` e `Locais`, mantendo filtros compartilhados de modalidade, distancia, nivel, objetivo e disponibilidade. Quando um modo nao tiver resultados, a experiencia deve orientar o perfil esportivo para uma acao produtiva, como ampliar raio, relaxar filtros ou criar uma sessao publica.

## Acceptance criteria

- [ ] `GET /api/discovery` aceita um modo de descoberta e retorna cards tipados para pessoas, sessoes e locais quando aplicavel.
- [ ] Filtros essenciais funcionam de forma consistente entre modos: modalidade, distancia/raio, nivel, objetivo e disponibilidade.
- [ ] Cards de pessoa exibem modalidade, nivel, disponibilidade resumida, bairro/distancia aproximada e motivo de recomendacao.
- [ ] Cards de sessao exibem modalidade, anfitriao, horario, local aproximado, vagas/status e regra de entrada.
- [ ] Estados vazios retornam sugestoes acionaveis, como ampliar distancia, remover filtro de nivel ou criar sessao publica.
- [ ] Testes cobrem filtros, payload tipado e estados vazios sem expor coordenada precisa.

## Blocked by

- esporteam-back/issues/01-sport-profile-onboarding.md
- esporteam-back/issues/02-sports-and-preferences.md
- esporteam-back/issues/03-availability-windows.md
- esporteam-back/issues/04-discovery-feed.md

