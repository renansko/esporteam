# NotificationPolicy

## `decide(NotificationActivity $activity)`

Retorna pares de Perfil Esportivo e subscription para menção direta, resposta ao
destinatário ou anúncio do Anfitrião. Suprime mute, conversa arquivada, bloqueio,
preferência desligada e subscriptions inativas.

## `DeliverConversationPush::handle`

Cria uma entrega idempotente, chama o adapter e registra sucesso, retry ou endpoint
inválido. O job não decide elegibilidade nem altera o estado durável de não lidos.
