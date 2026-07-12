# AiAuditEvent

Rastro operacional interno e sanitizado de uma chamada de Bio Assistida ou de
embedding. Não possui rota ou Resource público.

## Campos

- `sport_profile_id`: correlação privada com o Perfil Esportivo; pode ser nulo
  quando ainda não existe perfil.
- `operation` e `outcome`: geração/embedding e sucesso, falha ou rate limit.
- `idempotency_key`: única; evita repetir o mesmo resultado em reprocessamentos.
- `metadata`: somente provider, modelo, versão, tokens, duração, retry, fallback,
  rate limit e categoria de falha; nunca prompt, bio, coordenada, email, chave ou
  corpo bruto do provider.
