# SportSessionService

## createForUser

Assinatura: `createForUser(int $userId, array $data): SportSession`

Cria uma Sessao Esportiva para o Perfil Esportivo autenticado. Ignora qualquer autoria enviada no payload e usa o perfil resolvido por `user_id`.

Side effects: grava `sport_sessions` e adiciona o criador em `session_participants` com status `joined`.

## openSessions

Assinatura: `openSessions(array $filters = []): Collection`

Lista sessoes `open` e `public`, ordenadas por `starts_at` e limitadas a 50 itens. Filtros atuais: `sport_id`, `sport_slug`, `type`, `city`, `region`, `starts_after`, `starts_before`.

Side effects: nenhum.

## join

Assinatura: `join(int $userId, SportSession $session): SportSession`

Registra participacao do Perfil Esportivo autenticado em uma sessao existente. A sessao e travada em transacao antes de contar participantes.

Falha com validacao quando a sessao nao esta `open`, a capacidade esta cheia ou o perfil ja entrou.

Side effects: cria ou reativa linha em `session_participants`.
