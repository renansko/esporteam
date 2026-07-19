# EventConversationService

## openConversation

`openConversation(userId, session, cursor, limit)` autoriza o Perfil Esportivo, cria a conversa sob demanda e retorna apenas mensagens posteriores ao cursor, em ordem de `id`.

## postMessage

`postMessage(userId, session, body, clientMessageId)` sanitiza texto, grava em transação e retorna a mensagem original em retries idempotentes. Depois do commit, transmite a nova mensagem sem colocar a persistência em risco se o broadcast falhar.

## applySocialAction

`applySocialAction(userId, session, command)` recebe uma intenção validada (`reply`, `mention`, `reaction`, `read`, `mute` ou `typing`). Mantém autorização, mesma conversa, unicidade e cursor monotônico no módulo; typing só é difundido em tempo real e não é persistido.
