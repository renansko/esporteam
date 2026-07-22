# Log

| Data       | Issue | Escopo                                                       |
|------------|-------|--------------------------------------------------------------|
| 2026-05-11 | —     | bootstrap — Brain inicializado                               |
| 2026-05-20 | #2    | JWT esporteam-auth + WorkspaceClient + envelope + Pest            |
| 2026-05-20 | #3    | Idea entity + IdeaIngestionService + POST/GET /api/ideas     |
| 2026-05-24 | #7    | Tracer Pilar 1: RoadmapItem + ClusteringRun + ClusteringDecision + LLM hub (Anthropic/OpenAI/Fake) + pre-cluster pgvector + ClusteringService (LLM+fallback+circuit breaker+retry) + ClusterIdeasJob (queue dedicada) + watchdog + cost guard + 6 endpoints (cursor pagination) + Reverb broadcast + canal de log dedicado + embedding na ingestão + backfill |
| 2026-07-06 | #03   | AvailabilityWindow documentada + Descoberta com filtro basico de sobreposicao de disponibilidade |
| 2026-07-06 | #05   | SportSession + SessionParticipant + endpoints de criar/listar/entrar em sessoes |
| 2026-07-07 | #07   | Connections and Safety: interest em conexoes, reports, POST /api/reports, contexto minimo congelado para moderacao e status da issue concluido. |
| 2026-07-07 | #08   | DemoSeeder completo: 40 perfis, 8 professores, 15 aulas, 20 sessoes abertas gratuitas, participantes, conexoes, bloqueios e denuncias. |
| 2026-07-07 | #09   | Descoberta com modos people, sessions e places; filtros compartilhados por modalidade, distancia, nivel, objetivo e disponibilidade; cards tipados e empty_state acionavel. |
| 2026-07-07 | #10   | Sessoes hospedadas com recomendacoes para anfitriao, convites, aceite/recusa, pedidos com aprovacao e estados de participante para match em grupo. |
| 2026-07-07 | #12   | PostMatchSportActionService: proximas acoes apos match aceito, sugestoes de disponibilidade/local e criacao/vinculo de Sessao Esportiva gratuita. |
| 2026-07-11 | #15   | Validacao de historico de participacao: limite, estados persistidos, `left`, vazio e autenticacao |
| 2026-07-11 | —     | Descoberta: cache Redis versionado, rate limit por usuário para feed/mapa e retry isolado no front |
| 2026-07-12 | #17   | Bio Assistida: agente estruturado, rascunhos privados e API |
| 2026-07-12 | #18 | Aceite idempotente de BioSuggestion e embedding assíncrono da bio aceita |
| 2026-07-12 | #20 | Auditoria operacional segura e idempotente de Bio Assistida e embeddings |
| 2026-07-12 | #21 | Front do fluxo de Bio Assistida e contrato persistente de onboarding, histórico paginado, erros estruturados e criação idempotente. |
| 2026-07-17 | #26 | Series semanais: regra duradoura, ocorrencias em horizonte movel de 90 dias, idempotencia, DST e scheduler de reparo. |
| 2026-07-17 | #27 | Participação por ocorrência, acompanhamento idempotente de série e superfície agrupada de Eventos. |
| 2026-07-17 | #28 | Gestão de exceções, mudanças futuras e cancelamento de ocorrências recorrentes. |
| 2026-07-19 | #29 | EventConversation e EventMessage: histórico durável, autorização, cursor, idempotência e broadcast Reverb. |
| 2026-07-19 | #30 | Ações sociais da conversa: reply, menção, reações, leitura monotônica, mute e estado de não lidos. |
| 2026-07-19 | #31 | Conversation Media: fotos privadas, processamento por scanners, variantes seguras e vínculo à mensagem. |
| 2026-07-20 | #33 | Notification Policy, subscriptions/preferências Web Push, adapter VAPID/fake, job idempotente, service worker e lifecycle HTTP. |
| 2026-07-22 | — | Descoberta publica preserva todos os Perfis Esportivos e Sessoes; distancia apenas prioriza proximidade; agenda sem perfil retorna vazio. |
