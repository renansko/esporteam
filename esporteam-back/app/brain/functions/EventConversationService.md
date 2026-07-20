# EventConversationService

## openConversation

`openConversation(userId, session, cursor, limit)` autoriza o Perfil Esportivo, cria a conversa sob demanda e retorna apenas mensagens posteriores ao cursor, em ordem de `id`.

## postMessage

`postMessage(userId, session, body, clientMessageId)` sanitiza texto, grava em transação e retorna a mensagem original em retries idempotentes. Depois do commit, transmite a nova mensagem sem colocar a persistência em risco se o broadcast falhar.

## applySocialAction

`applySocialAction(userId, session, command)` recebe uma intenção validada (`reply`, `mention`, `reaction`, `read`, `mute`, `typing`, `remove`, `hide`, `mute_profile`, `ban` ou `announce`). Mantém autorização, mesma conversa, unicidade, sanções e cursor monotônico no módulo; remoções e ocultações preservam a evidência e expõem somente um tombstone. Sanções e anúncios do Anfitrião da Sessão geram auditoria não pública; conversa arquivada só permite leitura.
