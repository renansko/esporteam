# Resume - First PRD

Atualizado em 2026-07-07.

## Validacao De Push

- Branch local: `main`.
- `origin/main` validado via `git ls-remote` em `7ea7d1048742b7b328c0cad64117737e31d91a09`.
- Issue 12 esta no commit `7ea7d10` (`Complete post match sport action issue`), que e o HEAD remoto de `main`.
- Issue 13 esta no commit `fefbd13` (`Implement trust and safety discovery cards`) e e ancestral de `7ea7d10`.
- Portanto, issues 12 e 13 estao na `main` remota.

## Produto

Esporteam Mobile MVP e um app de descoberta esportiva local para conectar perfis esportivos, professores, aulas, grupos e sessoes proximas.

O loop principal entregue:

1. Perfil esportivo cria identidade publica com localizacao aproximada.
2. Perfil escolhe modalidades, nivel, objetivos e disponibilidade.
3. Discovery mostra pessoas, professores, sessoes e locais proximos.
4. Perfil pode criar ou entrar em sessoes esportivas gratuitas.
5. Professor pode criar aulas/ofertas profissionais com preco.
6. Conexoes, bloqueios e denuncias protegem a experiencia.
7. Matches e convites aceitos geram proximas acoes esportivas concretas.

## Decisoes De Produto

- Sessoes esportivas sao gratuitas para participantes.
- Preco pertence a aulas/ofertas profissionais (`class_offerings`) e perfil profissional (`teacher_profiles`), nao a `sport_sessions`.
- Organizadores e entusiastas podem ter assinatura de plataforma no futuro, confirmada por microservico de pagamentos, sem transformar sessoes em eventos pagos.
- Localizacao publica deve ser aproximada. Payloads publicos nao expõem coordenadas precisas de perfis.
- Bloqueio remove descoberta mutua, recomendacoes, convites e fluxos de sessao entre os perfis.
- Chat completo, pagamento in-app, reserva de quadra e assinatura premium ficaram fora do primeiro MVP.

## Entregas Por Issue

- 01 - Sport Profile Onboarding: perfil esportivo, `GET/PUT /api/profile`, localizacao aproximada e isolamento por `user_id`.
- 02 - Sports And Preferences: modalidades iniciais, preferencias do perfil, nivel e objetivos.
- 03 - Availability Windows: janelas de disponibilidade e filtro de sobreposicao.
- 04 - Discovery Feed: discovery por esporte, distancia, nivel, disponibilidade, ranking deterministico e privacidade.
- 05 - Sport Sessions: criacao/listagem/entrada em sessoes, participantes, status, capacidade e rejeicao de campos de cobranca.
- 06 - Teachers And Classes: perfil profissional, aulas, preco de aula, filtros e interesse de aluno.
- 07 - Connections And Safety: amizade/interesse/bloqueio, denuncias e contexto minimo de moderacao.
- 08 - Demo Seed Dataset: dataset idempotente com modalidades, perfis, professores, aulas, sessoes gratuitas, conexoes, bloqueios e denuncias.
- 09 - Discovery Modes And Empty States: modos `people`, `sessions`, `places`, filtros compartilhados e estados vazios acionaveis.
- 10 - Hosted Group Match: recomendacoes do anfitriao, convites, aceite/recusa, aprovacao, remocao e regras de seguranca.
- 11 - Public Sessions Without Match: entrada por convite, publica direta ou publica com aprovacao, com `next_action`.
- 12 - Post Match Sport Action: proximas acoes apos match/grupo, sugestao de horario/local e criacao/vinculo de sessao gratuita.
- 13 - Trust And Safety In Discovery: cards publicos com sinais de confianca, acoes de bloquear/denunciar, participantes aprovados e payload publico sem coordenadas de perfis.

## APIs Principais

- `GET /api/me`
- `GET /api/sports`
- `GET /api/profile`
- `PUT /api/profile`
- `PUT /api/profile/sports`
- `PUT /api/profile/availability`
- `GET /api/discovery`
- `POST /api/connections`
- `PATCH /api/connections/{connection}`
- `POST /api/reports`
- `GET /api/sessions`
- `POST /api/sessions`
- `POST /api/sessions/{session}/join`
- `GET /api/sessions/{session}/recommendations`
- `POST /api/sessions/{session}/invites`
- `PATCH /api/sessions/{session}/invite`
- `PATCH /api/sessions/{session}/participants/{profile}`
- `GET /api/teacher-profile`
- `PUT /api/teacher-profile`
- `GET /api/classes`
- `POST /api/classes`
- `POST /api/classes/{classOffering}/interest`
- `GET /api/groups`
- `POST /api/groups`
- `GET /api/post-match-actions`
- `POST /api/post-match-actions/session`

## Validacao Tecnica

Historico recente validado antes do fechamento:

- `php artisan test` passou em Docker em 2026-07-07 com 145 testes e 712 assertions.
- Testes focados de discovery, sessoes, seed, post-match e seguranca passaram em Docker ao longo da implementacao.
- Na ultima validacao local: `./vendor/bin/pint --test`, `php artisan route:list --path=api` e `git diff --check` passaram.
- O `git fetch` local falhou por `.git/FETCH_HEAD` read-only no sandbox, mas o remoto foi validado por `git ls-remote`.

## Estado Final

As issues 01 a 13 foram concluidas e consolidadas neste resumo. A pasta historica de issues pode ser removida porque o PRD inicial agora esta representado pelo codigo em `main`, pelos testes e por este arquivo de resumo.
