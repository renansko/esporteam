# PostMatchSportActionService

Modulo que transforma match aceito em acao esportiva concreta.

Ele aceita dois contextos: uma `Connection` de amizade aceita para match 1:1, ou uma `SportSession` com participacao ativa para match em grupo. O service calcula proximas acoes, sugestoes de disponibilidade/local e cria ou vincula uma Sessao Esportiva sem introduzir cobranca para participantes.

## Dependencias

- `Connection`, para match 1:1 aceito.
- `SportSession` e `SessionParticipant`, para grupos ativos e vinculo de sessao.
- `SportProfile`, `ProfileSport` e `AvailabilityWindow`, para modalidade, nivel, local aproximado e disponibilidade.
- `SportSessionService`, para criar sessoes preservando as regras existentes.
- transacoes via `DB`.

## Funcoes

- [`actionsForUser`](../functions/PostMatchSportActionService.md#actionsForUser)
- [`saveSessionForUser`](../functions/PostMatchSportActionService.md#saveSessionForUser)

## Observacoes

- Match 1:1 significa `connections.type = friendship` e `status = accepted`.
- Match em grupo significa sessao com o Perfil Esportivo autenticado em `joined` ou `approved`.
- Sessoes criadas a partir de uma conexao ficam `private`, `convite` e incluem o outro perfil como `approved`.
- O endpoint rejeita campos de preco/taxa/pagamento; assinatura de plataforma segue fora da sessao.
