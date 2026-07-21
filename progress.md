# Progresso do PRD #22

Status: concluído em 20/07/2026.

PRD: [Mapa mobile, Sessões Esportivas recorrentes e conversa social](https://github.com/renansko/esporteam/issues/22).

## Issues finalizadas

- #23 — Elegibilidade adulta para publicar, participar, acompanhar e conversar em Sessões Esportivas.
- #24 — Mapa como entrada principal da Descoberta, com Lista equivalente e acessível.
- #25 — Publicação de Sessão Esportiva pontual pelo Mapa, com localização pública aproximada e ponto exato protegido.
- #26 — Séries semanais recorrentes, timezone IANA, materialização idempotente e horizonte futuro.
- #27 — Acompanhamento de séries separado da participação em ocorrências.
- #28 — Edição de ocorrência, alteração desta e das próximas, cancelamento e controle de concorrência.
- #29 — Conversa textual durável em Sessões Esportivas, com autorização, bloqueios, cursor e idempotência.
- #30 — Respostas, menções elegíveis, reações, digitação, leitura monotônica, mute e não lidos.
- #31 — Fotos com upload privado, processamento, remoção de metadados, malware/content safety e estados visíveis.
- #32 — Moderação, sanções independentes da participação, denúncias, auditoria, anúncios e lifecycle de conversas.
- #33 — Política de notificação seletiva e Web Push para menções, respostas e anúncios.

## Entregas técnicas

- Módulos de Descoberta no Mapa, Session Hosting, Event Conversation, Conversation Media e Notification Policy.
- Modelos, migrations, requests, resources, controllers, jobs, eventos e rotas para sessões, séries, conversas, mídia e push.
- Feature flags reversíveis: `recurring_events`, `event_social_chat` e `event_push_notifications`.
- Reverb/Echo para atualização ao vivo, com histórico persistido como fonte de verdade e reconciliação após reconnect.
- Adapters externos para MapTiler, storage, normalização de imagens, malware scan, content safety e VAPID; testes usam fakes determinísticos.
- Coleção Bruno, brain/wiki e runbook de Web Push atualizados.
- Contratos de renderização do front alinhados ao Mapa como aba inicial; Lista permanece alternativa acessível.
- Envelope de erro `adult_eligibility_required` preservado diretamente no middleware para evitar perda do código pelo tratamento de exceções do framework.

## Segurança e regras preservadas

- Sessões Esportivas continuam gratuitas; nenhum preço ou cobrança foi adicionado a sessões.
- Coordenadas exatas não entram na Descoberta pública; acesso depende de anfitrião ou participante confirmado.
- Capacidade restante não é exposta publicamente; contagem de participantes pode ser exibida.
- Bloqueios, perfil adulto, visibilidade, autorização de conversa e sanções são aplicados também nos endpoints diretos.
- Push é suprimido por mute, archive, bloqueio, banimento, inelegibilidade e duplicidade.

## Validação final

- Backend: `php artisan test` — **201 testes aprovados, 1079 assertions**.
- Front: `npm test` — contratos de serviços, composables e renderização aprovados.
- Front build: `npm run build` — build Vite aprovado.
- Formatação: Pint aprovado no middleware alterado.

## Commits da entrega

- `c93a6f1` — elegibilidade adulta (#23), já presente em `origin/main`.
- `75ec216` até `9f97724` — implementação das issues #24–#33.
- Commit final deste documento e dos ajustes de validação: registrado junto ao fechamento do PRD.

## Fora deste fechamento

- Deploy de produção, configuração de credenciais externas e ativação das feature flags dependem do ambiente operacional.
- O front mantém a integração com adapters reais preparada, mas os testes não fazem chamadas a provedores externos.
