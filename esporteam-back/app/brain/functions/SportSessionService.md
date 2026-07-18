# SportSessionService

## createForUser

Assinatura: `createForUser(int $userId, array $data): SportSession`

Cria uma Sessao Esportiva para o Perfil Esportivo autenticado. Ignora qualquer autoria enviada no payload e usa o perfil resolvido por `user_id`.

Aceita `entry_mode` como `convite`, `publica_direta` ou `publica_aprovacao`. O campo legado `requires_approval = true` ainda cria sessao `publica_aprovacao` quando `entry_mode` nao foi enviado. `min_level` e `max_level` definem faixa opcional de nivel elegivel.

Side effects: grava `sport_sessions` e adiciona o criador em `session_participants` com status `joined`.

## openSessions

Aceita bounds completos de viewport (`south`, `north`, `west`, `east`) para a superficie de Mapa. Quando presentes, limita a consulta a coordenadas aproximadas dentro do retangulo; a validacao HTTP impede viewport com mais de 10 graus em qualquer eixo.

Assinatura: `openSessions(int $userId, array $filters = []): Collection`

Lista sessoes `open` e `public`, ordenadas por `starts_at` e limitadas a 50 itens. Filtros atuais: `sport_id`, `sport_slug`, `type`, `entry_mode`, `level`, `distance_km`, `weekday`/`starts_at`/`ends_at`, `has_available_slots`, `city`, `region`, `starts_after`, `starts_before`.

Cada sessao recebe `next_action`: `entrar`, `pedir_vaga` ou `indisponivel`, calculado por modo de entrada, bloqueio, visibilidade do perfil, faixa de nivel e capacidade. O payload publico nao expõe vagas restantes.

Side effects: nenhum.

## detailForUser

Assinatura: `detailForUser(int $userId, SportSession $session): SportSession`

Retorna detalhe de Sessao Esportiva publica e aberta. Oculta sessoes privadas/encerradas e sessoes cujo anfitriao bloqueou ou foi bloqueado pelo Perfil Esportivo autenticado. Carrega participacao somente do perfil autenticado; capacidade continua privada para nao-anfitrioes.

Side effects: nenhum.

## participantSessionsForUser

Assinatura: `participantSessionsForUser(int $userId): Collection`

Lista ate 50 Sessoes Esportivas em que o Perfil Esportivo autenticado possui participacao, ordenadas da mais recente para a mais antiga. Inclui `joined`, `approved`, `interested`, `invited`, `declined`, `removed` e `left`; o front normaliza os cinco primeiros grupos para Partidas e deixa `left` fora da primeira interface, conforme PRD. Carrega somente registro de participacao do perfil autenticado para normalizacao segura no front.

Side effects: nenhum.

## recommendationsForHost

Assinatura: `recommendationsForHost(int $userId, SportSession $session, array $filters = []): Collection`

Lista perfis recomendados para o anfitriao convidar para a sessao. Filtra por modalidade da sessao, disponibilidade no horario da sessao, visibilidade publica, bloqueios, distancia, nivel e objetivo.

Side effects: nenhum.

## inviteProfiles

Assinatura: `inviteProfiles(int $userId, SportSession $session, array $profileIds): SportSession`

Permite ao anfitriao criar convites `invited` para perfis elegiveis. Rejeita perfis ocultos, bloqueados, ja participantes, capacidade insuficiente e campos de cobranca no request HTTP.

Side effects: cria ou atualiza linhas em `session_participants`.

## respondToInvite

Assinatura: `respondToInvite(int $userId, SportSession $session, string $action): SportSession`

Permite ao perfil convidado aceitar ou recusar convite. Aceite vira `approved`; recusa vira `declined`.

Side effects: atualiza status em `session_participants`.

## decideParticipant

Assinatura: `decideParticipant(int $userId, SportSession $session, SportProfile $profile, string $action): SportSession`

Permite ao anfitriao aprovar, recusar ou remover perfis do fluxo da sessao. Aprovacao respeita capacidade, bloqueios e visibilidade.

Side effects: atualiza status em `session_participants`.

## join

Assinatura: `join(int $userId, SportSession $session): SportSession`

Registra participacao do Perfil Esportivo autenticado em uma sessao existente. A sessao e travada em transacao antes de contar participantes. `publica_direta` cria `joined`; `publica_aprovacao` cria pedido `interested`; `convite` rejeita entrada publica.

Falha com validacao quando a sessao nao esta `open`, a capacidade esta cheia para entrada direta, o perfil esta bloqueado/oculto, nao respeita a faixa de nivel ou ja entrou/pediu vaga.

Side effects: cria ou reativa linha em `session_participants`.
