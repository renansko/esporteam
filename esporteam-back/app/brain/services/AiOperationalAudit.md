# AiOperationalAudit

Módulo único para registrar a observabilidade de Bio Assistida. Persiste eventos
idempotentes e publica um log operacional com a mesma carga sanitizada.

## Dependências

- `AiAuditEvent` para a persistência interna e única por chave.

## Contratos

- [[functions/AiOperationalAudit]] — registra um resultado seguro de IA.
