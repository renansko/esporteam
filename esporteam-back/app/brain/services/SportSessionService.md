# SportSessionService

Modulo dono dos casos de uso de Sessoes Esportivas.

Ele preserva `SportProfile` como identidade de participacao: callers passam `userId`, e o service resolve o Perfil Esportivo autenticado. Controllers ficam finos e nao decidem capacidade, status ou autoria.

## Dependencias

- `SportSession`
- `SportProfile`
- `session_participants`
- transacoes via `DB`

## Funcoes

- [`createForUser`](../functions/SportSessionService.md#createForUser)
- [`openSessions`](../functions/SportSessionService.md#openSessions)
- [`join`](../functions/SportSessionService.md#join)
