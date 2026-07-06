# Index do brain

## Conventions

- [`conventions/HttpResponses.md`](conventions/HttpResponses.md) — envelope, paginação (page + cursor), formatos de erro
- [`conventions/Testing.md`](conventions/Testing.md) — TDD com Pest, helper `actingAsWorkspace`, naming

## Product

- [`product/SportDiscovery.md`](product/SportDiscovery.md) — direção atual do produto: descoberta esportiva local, professores, aulas, sessoes e amigos

## Entities

> Entidades abaixo ainda refletem a base tecnica herdada. Elas devem ser migradas para o dominio esportivo em cortes pequenos, preservando os padrões de arquitetura.

- [`entities/AvailabilityWindow.md`](entities/AvailabilityWindow.md) — Disponibilidade recorrente de um Perfil Esportivo
- [`entities/Idea.md`](entities/Idea.md) — entrada bruta, schema da tabela `ideas` (com embedding)
- [`entities/RoadmapItem.md`](entities/RoadmapItem.md) — item priorizado do roadmap + recomputeScore
- [`entities/ClusteringRun.md`](entities/ClusteringRun.md) — execução de clustering com cost/cache/fallback tracking
- [`entities/ClusteringDecision.md`](entities/ClusteringDecision.md) — auditoria atômica do trace LLM

## Services

- [`services/DiscoveryService.md`](services/DiscoveryService.md) — listagem determinística inicial de Perfis Esportivos para Descoberta
- [`services/IdeaIngestionService.md`](services/IdeaIngestionService.md) — ponto único de criação de Ideas (gera embedding)
- [`services/ClusteringService.md`](services/ClusteringService.md) — orquestrador da run (LLM + fallback)
- [`services/LlmFactory.md`](services/LlmFactory.md) — hub multi-provider de LLM/Embedding

## Functions

- [`functions/DiscoveryService.md`](functions/DiscoveryService.md) — `profilesForUser` e filtros básicos
- [`functions/IdeaIngestionService.md`](functions/IdeaIngestionService.md) — contratos das funções públicas
- [`functions/ClusteringService.md`](functions/ClusteringService.md) — `executeRun` e side effects

## Resources

- [`resources/IdeaResource.md`](resources/IdeaResource.md) — shape HTTP de Ideia

---

Log de mudanças: [`log.md`](log.md).
