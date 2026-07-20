# EventMessage

Mensagem textual durável da conversa de uma Sessão Esportiva.

## Campos e invariantes

- `event_conversation_id` e `author_profile_id` identificam conversa e autor.
- `client_message_id` é UUID idempotente por autor/conversa.
- `body` é texto sanitizado; HTML não é preservado.
- O `id` crescente é o cursor estável do histórico.
- `reply_to_event_message_id` mantém referência segura; uma resposta sobrevive à remoção do conteúdo original.
- Menções e reações são relações estruturadas, idempotentes e agregadas no recurso HTTP.
- `status` preserva tombstone de moderação: `removed` para remoção pelo autor e `hidden` para ocultação pelo Anfitrião da Sessão. O conteúdo original permanece como evidência, mas não é entregue pelo recurso HTTP.
Mensagens têm `status` (`published`, `removed`, `hidden`) e `kind` (`message`, `announcement`, `system`). O conteúdo de tombstones não é serializado, embora a evidência persista para auditoria.
