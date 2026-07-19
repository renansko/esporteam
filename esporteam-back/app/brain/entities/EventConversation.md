# EventConversation

Conversa canônica criada sob demanda para uma Sessão Esportiva pontual. A sessão possui no máximo uma conversa, protegida pela chave única `sport_session_id`.

## Campos

- `sport_session_id`: Sessão Esportiva dona da conversa.
- `status`: ciclo de vida inicial (`active`); arquivamento/moderação é responsabilidade da próxima issue.

## Relações

- `session`: Sessão Esportiva.
- `messages`: mensagens ordenadas pelo cursor durável (`id`).
