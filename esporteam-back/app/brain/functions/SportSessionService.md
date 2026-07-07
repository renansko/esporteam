# SportSessionService

## createForUser

Assinatura: `createForUser(int $userId, array $data): SportSession`

Cria uma Sessao Esportiva para o Perfil Esportivo autenticado. Ignora qualquer autoria enviada no payload e usa o perfil resolvido por `user_id`.

Side effects: grava `sport_sessions` e adiciona o criador em `session_participants` com status `joined`.

## openSessions

Assinatura: `openSessions(array $filters = []): Collection`

Lista sessoes `open` e `public`, ordenadas por `starts_at` e limitadas a 50 itens. Filtros atuais: `sport_id`, `sport_slug`, `type`, `city`, `region`, `starts_after`, `starts_before`.

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

Registra participacao do Perfil Esportivo autenticado em uma sessao existente. A sessao e travada em transacao antes de contar participantes. Quando `requires_approval = true`, cria pedido `interested` em vez de entrada direta.

Falha com validacao quando a sessao nao esta `open`, a capacidade esta cheia ou o perfil ja entrou.

Side effects: cria ou reativa linha em `session_participants`.
