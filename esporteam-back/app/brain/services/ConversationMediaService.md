# ConversationMediaService

Módulo profundo para fotos seguras em Conversa de Sessão Esportiva. Centraliza autorização, reserva de upload, inspeção real, scanners, normalização, estado e vínculo com mensagem.

Depende de EventConversation e dos ports de armazenamento, malware, segurança visual e normalização de imagem. Produção usa S3 compatível, ClamAV, AWS Rekognition e ImageMagick; testes os substituem por fakes determinísticos.

- [`prepareUpload`](../functions/ConversationMediaService.md#prepareupload)
- [`processUpload`](../functions/ConversationMediaService.md#processupload)
