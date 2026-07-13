# BioSuggestion

Rascunho privado de bio gerado para um Perfil Esportivo. Nunca publica ou altera
`sport_profiles.bio` automaticamente.

## Campos

- `sport_profile_id`: proprietário do rascunho.
- `status`: `generating`, `generated`, `accepted` ou `failed`.
- `generated_bio` e `structured_output`: resultado estruturado validado.
- `prompt_version`, `provider`, `model`: rastreabilidade da geração.
- `tokens_input`, `tokens_output`: uso mínimo para observabilidade.
- `failure_code`, `metadata`: falha sanitizada, sem payload do provider.
- `context_fingerprint`: hash do contexto permitido, não o contexto bruto.
- `idempotency_key`: chave opcional e única por Perfil Esportivo para repetir
  uma criação sem chamar o agente novamente.

## Segurança

A relação pertence a um único Perfil Esportivo. Controllers nunca recebem um
ID arbitrário de perfil para listar rascunhos; o proprietário vem do JWT.

Aceitar um rascunho copia somente `generated_bio` validado para a bio pública e
agenda o embedding após o commit. Um rascunho já aceito é idempotente.
