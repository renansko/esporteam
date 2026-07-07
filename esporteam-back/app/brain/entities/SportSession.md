# SportSession

Sessao esportiva pontual criada por um Perfil Esportivo para partida, treino, corrida, aula aberta ou encontro.

## Campos

- `creator_profile_id`: Perfil Esportivo que criou a sessao.
- `sport_id`: modalidade opcional.
- `title`, `description`, `type`, `starts_at`.
- `location_label`, `city`, `region`, `latitude_approx`, `longitude_approx`.
- `capacity`: limite total de participantes ativos, incluindo o criador.
- `visibility`: `public` ou `private`.
- `status`: `open`, `cancelled` ou `completed`.

## Relacionamentos

- `creator`: belongsTo `SportProfile`.
- `sport`: belongsTo `Sport`.
- `participationRecords`: hasMany `SessionParticipant`.
- `participants`: belongsToMany `SportProfile` via `session_participants`.

## Regras

`SportSessionService` cria a sessao, adiciona o criador como participante `joined` e bloqueia entrada quando status nao e `open` ou a capacidade esta cheia.
