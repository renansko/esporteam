# BioSuggestionService

## `createForUser`

Recebe o `userId` autenticado e uma orientação opcional. Exige modalidade ou
orientação, envia somente contexto esportivo permitido ao agente e grava uma
sugestão `generated` ou uma falha sanitizada. Não altera a bio pública.

## `listForUser`

Carrega o Perfil Esportivo pelo `userId` e retorna seus rascunhos mais recentes.
Não aceita IDs de outros perfis e não expõe fingerprint ou contexto enviado.
