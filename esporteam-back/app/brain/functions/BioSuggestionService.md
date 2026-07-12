# BioSuggestionService

## `createForUser`

Recebe o `userId` autenticado e uma orientação opcional. Exige modalidade ou
orientação, envia somente contexto esportivo permitido ao agente e grava uma
sugestão `generated` ou uma falha sanitizada. Não altera a bio pública.

## `listForUser`

Carrega o Perfil Esportivo pelo `userId` e retorna seus rascunhos mais recentes.
Não aceita IDs de outros perfis e não expõe fingerprint ou contexto enviado.

## `acceptForUser`

Encontra a sugestão pelo Perfil Esportivo autenticado, aceita somente um
rascunho `generated` e persiste sua bio validada. Cria/renova o registro de
embedding com hash da bio e agenda `GenerateProfileBioEmbedding` após o commit.
Repetir o aceite de uma sugestão `accepted` não altera dados nem agenda job.
