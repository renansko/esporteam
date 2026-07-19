# ConversationMediaService

## prepareUpload

`prepareUpload(userId, session, mime)` verifica maioridade e acesso à conversa, reserva mídia `processing` e devolve um destino privado, assinado e curto para JPEG, PNG ou WebP.

## processUpload

`processUpload(mediaId)` é idempotente: verifica tamanho e conteúdo real, executa ClamAV e Rekognition, normaliza/remove metadados e publica as variantes somente após todas as aprovações. Falhas viram `rejected` com código seguro.
