# SessionParticipant

Pivot entre Perfil Esportivo e Sessao Esportiva.

## Campos

- `sport_session_id`: sessao.
- `sport_profile_id`: perfil participante.
- `status`: `joined`, `left`, `invited`, `interested`, `approved`, `declined` ou `removed`.

## Invariantes

- Par `(sport_session_id, sport_profile_id)` e unico.
- Participantes com `status = joined` ou `approved` contam como participantes ativos.
- Convites com `status = invited` reservam vaga para evitar overbooking.
- Reentrada futura deve atualizar a linha existente em vez de criar duplicata.

## Relacionamentos

- `session`: belongsTo `SportSession`.
- `profile`: belongsTo `SportProfile`.
