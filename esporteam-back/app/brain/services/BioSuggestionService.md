# BioSuggestionService

Módulo de Bio Assistida. Monta o contexto esportivo permitido, chama o
`BioAssistant` do Laravel AI SDK, valida a saída e persiste o rascunho privado.

## Dependências

- `BioAssistant` — agente estruturado, sem tools ou acesso a banco.
- `SportProfile` e relações de práticas/disponibilidade.
- `BioSuggestion` — histórico privado e metadados sanitizados.

Também aceita explicitamente um rascunho do dono, atualiza a bio pública em
transação e agenda seu embedding depois do commit.

## Contratos

- [[functions/BioSuggestionService]] — criar, listar e aceitar sugestões do perfil atual.
