# BioSuggestionService

Módulo de Bio Assistida. Monta o contexto esportivo permitido, chama o
`BioAssistant` do Laravel AI SDK, valida a saída e persiste o rascunho privado.

## Dependências

- `BioAssistant` — agente estruturado, sem tools ou acesso a banco.
- `SportProfile` e relações de práticas/disponibilidade.
- `BioSuggestion` — histórico privado e metadados sanitizados.

## Contratos

- [[functions/BioSuggestionService]] — criar e listar sugestões do perfil atual.
