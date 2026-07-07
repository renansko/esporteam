# SportSessionService

Modulo dono dos casos de uso de Sessoes Esportivas e match em grupo hospedado.

Ele preserva `SportProfile` como identidade de participacao: callers passam `userId`, e o service resolve o Perfil Esportivo autenticado. Controllers ficam finos e nao decidem capacidade, status ou autoria.

## Dependencias

- `SportSession`
- `SportProfile`
- `session_participants`
- `Connection`, para bloquear convite/aprovacao entre perfis bloqueados.
- transacoes via `DB`

## Funcoes

- [`createForUser`](../functions/SportSessionService.md#createForUser)
- [`openSessions`](../functions/SportSessionService.md#openSessions)
- [`recommendationsForHost`](../functions/SportSessionService.md#recommendationsForHost)
- [`inviteProfiles`](../functions/SportSessionService.md#inviteProfiles)
- [`respondToInvite`](../functions/SportSessionService.md#respondToInvite)
- [`decideParticipant`](../functions/SportSessionService.md#decideParticipant)
- [`join`](../functions/SportSessionService.md#join)

## Observacoes

- O anfitriao e o `creator_profile_id` da sessao.
- `entry_mode` define entrada: `convite` bloqueia join publico, `publica_direta` entra como `joined`, `publica_aprovacao` cria pedido `interested`.
- `requires_approval` continua sincronizado por compatibilidade, mas `entry_mode` e a linguagem principal do dominio.
- Convites usam `invited`; aceite vira `approved`, recusa vira `declined`.
- Capacidade conta `joined` e `approved`; convites `invited` reservam vagas antes do aceite.
- `GET /api/sessions` calcula `next_action` e filtra por distancia, nivel, horario e disponibilidade interna de vagas sem expor a quantidade de vagas restantes.
- Nenhum fluxo de match em grupo aceita preco, taxa ou pagamento por vaga.
