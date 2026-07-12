# BioSuggestion

Rascunho privado de bio gerado para um Perfil Esportivo. Nunca publica ou altera
`sport_profiles.bio` automaticamente.

## Campos

- `sport_profile_id`: proprietário do rascunho.
- `status`: `generating`, `generated` ou `failed`.
- `generated_bio` e `structured_output`: resultado estruturado validado.
- `prompt_version`, `provider`, `model`: rastreabilidade da geração.
- `tokens_input`, `tokens_output`: uso mínimo para observabilidade.
- `failure_code`, `metadata`: falha sanitizada, sem payload do provider.
- `context_fingerprint`: hash do contexto permitido, não o contexto bruto.

## Segurança

A relação pertence a um único Perfil Esportivo. Controllers nunca recebem um
ID arbitrário de perfil para listar rascunhos; o proprietário vem do JWT.
