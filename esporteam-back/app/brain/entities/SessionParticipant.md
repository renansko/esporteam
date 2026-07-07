# SessionParticipant

Pivot entre Perfil Esportivo e Sessao Esportiva.

## Campos

- `sport_session_id`: sessao.
- `sport_profile_id`: perfil participante.
- `status`: `joined` ou `left`.

## Invariantes

- Par `(sport_session_id, sport_profile_id)` e unico.
- Participantes com `status = joined` contam para capacidade.
- Reentrada futura deve atualizar a linha existente em vez de criar duplicata.

## Relacionamentos

- `session`: belongsTo `SportSession`.
- `profile`: belongsTo `SportProfile`.
