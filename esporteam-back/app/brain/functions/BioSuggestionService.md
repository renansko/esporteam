# BioSuggestionService

## `createForUser`

Recebe o `userId` autenticado, orientação opcional e `Idempotency-Key`. Exige
modalidade ou orientação, envia somente contexto esportivo permitido ao agente
e grava uma sugestão `generated` ou uma falha sanitizada. Repetir a mesma chave
retorna o resultado persistido sem uma nova chamada ao agente.

## `listForUser`

Carrega o Perfil Esportivo pelo `userId` e pagina seus rascunhos mais recentes.
Não aceita IDs de outros perfis e não expõe fingerprint ou contexto enviado.

## `acceptForUser`

Encontra a sugestão pelo Perfil Esportivo autenticado, aceita somente um
rascunho `generated` e persiste sua bio validada. Cria/renova o registro de
embedding com hash da bio, conclui o onboarding de Bio Assistida e agenda
`GenerateProfileBioEmbedding` após o commit. Repetir o aceite de uma sugestão
`accepted` não altera dados nem agenda job.
