# PostMatchSportActionService

## actionsForUser

Assinatura: `actionsForUser(int $userId, array $data): array`

Retorna proximas acoes para um match 1:1 aceito (`connection_id`) ou para um grupo ativo (`session_id`). O payload inclui contexto, acoes, sugestoes de horario, sugestoes de local e motivos como `same_sport`, `compatible_level`, `available` e `active_group`.

Parametros: `connection_id` ou `session_id`.

Side effects: nenhum.

Entidades tocadas: `Connection`, `SportSession`, `SessionParticipant`, `SportProfile`, `ProfileSport`, `AvailabilityWindow`.

## saveSessionForUser

Assinatura: `saveSessionForUser(int $userId, array $data): SportSession`

Cria uma Sessao Esportiva privada a partir de uma conexao aceita, ou vincula/atualiza a sessao existente de um grupo ativo com horario e local escolhidos. Em conexoes, a modalidade comum e usada quando `sport_id` nao e enviado.

Parametros principais: `connection_id` ou `session_id`, `starts_at`, um dado de local (`location_label`, cidade/regiao ou coordenada aproximada), e campos opcionais de titulo/tipo/capacidade.

Side effects: cria ou atualiza `sport_sessions`; adiciona participantes aceitos quando cria a sessao a partir de match 1:1.

Entidades tocadas: `Connection`, `SportSession`, `SessionParticipant`, `SportProfile`.
