# BioSuggestionService

Módulo de Bio Assistida. Monta o contexto esportivo permitido, chama o
`BioAssistant` do Laravel AI SDK, valida a saída e persiste o rascunho privado.

## Dependências

- `BioAssistant` — agente estruturado, sem tools ou acesso a banco.
- `SportProfile` e relações de práticas/disponibilidade.
- `BioSuggestion` — histórico privado e metadados sanitizados.
- `AiOperationalAudit` — custo, duração, resultado e falhas sem conteúdo privado.

Também pagina o histórico privado, atende criação idempotente por chave e aceita
explicitamente um rascunho do dono. O aceite atualiza a bio pública, conclui o
onboarding persistente e agenda seu embedding depois do commit.

## Contratos

- [[functions/BioSuggestionService]] — criar, listar e aceitar sugestões do perfil atual.
