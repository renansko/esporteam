# EventConversationService

Módulo profundo de conversa de Sessão Esportiva pontual. Centraliza criação canônica, autorização de perfil adulto (na borda), bloqueios, limites, cursor e idempotência.

## Dependências

- `SportSession`, `SportProfile`, `Connection`, `EventConversation`, `EventMessage`.
- Transações e `afterCommit` para emissão do broadcast.

## Funções

- [`openConversation`](../functions/EventConversationService.md#openConversation)
- [`postMessage`](../functions/EventConversationService.md#postMessage)
