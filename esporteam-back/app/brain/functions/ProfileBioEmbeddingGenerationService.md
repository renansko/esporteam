# ProfileBioEmbeddingGenerationService

## `generate`

Recebe o Perfil Esportivo, hash da bio e tentativa da fila. Chama o provider
somente para a bio ainda atual, persiste um vetor válido e registra sucesso ou
falha sem salvar o conteúdo da bio nem erros brutos do provider.
