# NotificationPolicy

Módulo profundo que recebe uma `NotificationActivity` e retorna subscriptions elegíveis.
Ele encapsula tipo de atividade, destinatário, mute, arquivamento, bloqueio, preferência
global e deduplicação posterior do job. Mensagens comuns, reações, digitação e leitura
nunca atravessam a política.

## Adapters

- `FakeConversationPushAdapter` — determinístico para testes.
- `VapidConversationPushAdapter` — Web Push criptografado com VAPID.

## Lifecycle

Subscriptions são renovadas por dispositivo, desativadas no logout/preferência e removidas
quando o endpoint retorna 404/410. `DeliverConversationPush` usa chave estável por
atividade, destinatário e subscription.
