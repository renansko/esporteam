# ProfileBioEmbedding

Registro único do embedding da bio atual aceita de um Perfil Esportivo.
Mantém somente o hash da fonte, nunca texto adicional ou contexto privado.

## Campos

- `sport_profile_id`: Perfil Esportivo proprietário, único.
- `embedding`: vetor `vector(1536)` no PostgreSQL e JSON nos testes.
- `status`: `pending`, `completed` ou `failed`.
- `model`, `source_hash`, `embedded_at`: rastreabilidade do vetor atual.
- `failure_code`, `metadata`: observabilidade sanitizada de falhas.

## Invariantes

- O provider recebe exclusivamente `sport_profiles.bio` quando seu hash coincide
  com `source_hash`.
- Reexecuções para um vetor `completed` não criam outro registro.
