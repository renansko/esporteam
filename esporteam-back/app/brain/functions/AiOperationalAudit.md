# AiOperationalAudit

## `record`

Recebe uma operação, resultado, correlação privada e chave de idempotência. Aceita
somente uma lista permitida de metadados operacionais e ignora qualquer outro
campo, incluindo erros brutos e dados pessoais. Persiste somente o primeiro evento
da chave e emite o log sanitizado quando ele é criado.

## `recordBioGenerationRateLimit`

Registra uma janela de rate limit pelo usuário autenticado. Resolve internamente
o Perfil Esportivo e usa o fim da janela como chave idempotente.
