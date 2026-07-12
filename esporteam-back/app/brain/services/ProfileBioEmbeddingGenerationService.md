# ProfileBioEmbeddingGenerationService

Executa o use case assíncrono do embedding da bio pública atual. Valida a fonte,
persiste o vetor ou a falha sanitizada e registra o resultado operacional.

## Dependências

- `EmbeddingClient` e `AiOperationalAudit`.
- `ProfileBioEmbedding` e `SportProfile`.

## Contratos

- [[functions/ProfileBioEmbeddingGenerationService]] — gera um vetor idempotente.
