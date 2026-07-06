# DiscoveryService

## profilesForUser

Assinatura: `profilesForUser(int $userId, array $filters = []): Collection`

Lista Perfis Esportivos publicos para a Descoberta. Remove o proprio perfil do usuario autenticado e carrega `sports.sport` e `availabilityWindows` para o payload HTTP.

## Parametros

- `userId`: id do usuario autenticado vindo do auth.
- `filters`: filtros validados pelo `IndexDiscoveryRequest`.

## Filtros

- `weekday`, `starts_at`, `ends_at`: quando os tres existem, aplica sobreposicao basica de disponibilidade.

## Side effects

Nenhum. Apenas leitura de `sport_profiles` e `availability_windows`.
