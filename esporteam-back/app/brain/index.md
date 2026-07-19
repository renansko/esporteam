# Index do brain

## Conventions

- [`conventions/HttpResponses.md`](conventions/HttpResponses.md) — envelope, paginação (page + cursor), formatos de erro
- [`conventions/Testing.md`](conventions/Testing.md) — TDD com Pest, helper `actingAsWorkspace`, naming

## Product

- [`product/SportDiscovery.md`](product/SportDiscovery.md) — direção atual do produto: descoberta esportiva local, professores, aulas, sessoes e amigos

## Entities

> Entidades abaixo ainda refletem a base tecnica herdada. Elas devem ser migradas para o dominio esportivo em cortes pequenos, preservando os padrões de arquitetura.

- [`entities/AvailabilityWindow.md`](entities/AvailabilityWindow.md) — Disponibilidade recorrente de um Perfil Esportivo
- [`entities/SportSession.md`](entities/SportSession.md) — Sessao Esportiva pontual ou hospedada por um Perfil Esportivo
- [`entities/SportSessionSeries.md`](entities/SportSessionSeries.md) — regra semanal duradoura e ocorrencias materializadas
- [`entities/EventConversation.md`](entities/EventConversation.md) — conversa canônica de uma Sessão Esportiva pontual
- [`entities/EventMessage.md`](entities/EventMessage.md) — mensagem textual durável e idempotente
- [`entities/SessionParticipant.md`](entities/SessionParticipant.md) — participacao, convite e interesse de Perfil Esportivo em uma Sessao Esportiva
- [`entities/Idea.md`](entities/Idea.md) — entrada bruta, schema da tabela `ideas` (com embedding)
- [`entities/RoadmapItem.md`](entities/RoadmapItem.md) — item priorizado do roadmap + recomputeScore
- [`entities/ClusteringRun.md`](entities/ClusteringRun.md) — execução de clustering com cost/cache/fallback tracking
- [`entities/ClusteringDecision.md`](entities/ClusteringDecision.md) — auditoria atômica do trace LLM
- [`entities/BioSuggestion.md`](entities/BioSuggestion.md) — rascunho privado de bio assistida
- [`entities/ProfileBioEmbedding.md`](entities/ProfileBioEmbedding.md) — vetor da bio aceita do Perfil Esportivo
- [`entities/AiAuditEvent.md`](entities/AiAuditEvent.md) — rastro operacional interno de Bio Assistida

## Services

- [`services/DiscoveryService.md`](services/DiscoveryService.md) — Descoberta determinística por modos de pessoas, sessões e locais
- [`services/DiscoveryCache.md`](services/DiscoveryCache.md) — Cache versionado das respostas de Descoberta e mapa
- [`services/SportSessionService.md`](services/SportSessionService.md) — criacao, listagem aberta, participacao e match em grupo de Sessoes Esportivas
- [`services/EventConversationService.md`](services/EventConversationService.md) — autorização, histórico e postagem de conversa de Sessão Esportiva
- [`services/PostMatchSportActionService.md`](services/PostMatchSportActionService.md) — proximas acoes, horario/local e sessao depois de match aceito
- [`services/IdeaIngestionService.md`](services/IdeaIngestionService.md) — ponto único de criação de Ideas (gera embedding)
- [`services/ClusteringService.md`](services/ClusteringService.md) — orquestrador da run (LLM + fallback)
- [`services/LlmFactory.md`](services/LlmFactory.md) — hub multi-provider de LLM/Embedding
- [`services/BioSuggestionService.md`](services/BioSuggestionService.md) — geração, aceite e isolamento de rascunhos de bio
- [`services/AiOperationalAudit.md`](services/AiOperationalAudit.md) — auditoria segura e idempotente de IA
- [`services/ProfileBioEmbeddingGenerationService.md`](services/ProfileBioEmbeddingGenerationService.md) — execução assíncrona do embedding

## Functions

- [`functions/DiscoveryService.md`](functions/DiscoveryService.md) — `discoverForUser`, `profilesForUser`, modos e filtros compartilhados
- [`functions/DiscoveryCache.md`](functions/DiscoveryCache.md) — cache versionado por usuário, superfície e filtros
- [`functions/SportSessionService.md`](functions/SportSessionService.md) — `createForUser`, `openSessions`, recomendacoes, convites, decisoes e `join`
- [`functions/EventConversationService.md`](functions/EventConversationService.md) — `openConversation` e `postMessage`
- [`functions/PostMatchSportActionService.md`](functions/PostMatchSportActionService.md) — `actionsForUser` e `saveSessionForUser`
- [`functions/IdeaIngestionService.md`](functions/IdeaIngestionService.md) — contratos das funções públicas
- [`functions/ClusteringService.md`](functions/ClusteringService.md) — `executeRun` e side effects
- [`functions/BioSuggestionService.md`](functions/BioSuggestionService.md) — criação, listagem e aceite de sugestões privadas
- [`functions/AiOperationalAudit.md`](functions/AiOperationalAudit.md) — contrato do rastro operacional
- [`functions/ProfileBioEmbeddingGenerationService.md`](functions/ProfileBioEmbeddingGenerationService.md) — geração auditada de vetor

## Resources

- [`resources/IdeaResource.md`](resources/IdeaResource.md) — shape HTTP de Ideia
- [`resources/SportSessionResource.md`](resources/SportSessionResource.md) — shape HTTP de Sessao Esportiva

---

Log de mudanças: [`log.md`](log.md).
