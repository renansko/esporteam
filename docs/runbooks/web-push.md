# Runbook: Web Push

Ative `FEATURE_EVENT_PUSH_NOTIFICATIONS` somente após configurar:

- `WEBPUSH_VAPID_SUBJECT` (mailto ou URL operacional)
- `WEBPUSH_VAPID_PUBLIC_KEY`
- `WEBPUSH_VAPID_PRIVATE_KEY`

O worker deve consumir a fila padrão para executar `DeliverConversationPush`. A flag
desliga prompt, registro e envio sem afetar conversa, badges ou não lidos.

Falhas 404/410 desativam a subscription. Falhas transitórias são repetidas pelo job
com backoff. Inspecione `push_deliveries` por `status`, `attempts` e `failure_code`.
O payload deliberadamente não contém texto integral, foto, ponto exato ou segredo.
