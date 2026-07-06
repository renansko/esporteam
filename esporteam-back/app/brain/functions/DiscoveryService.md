# DiscoveryService

## profilesForUser

Assinatura: `profilesForUser(int $userId, array $filters = []): Collection`

Lista cards de Perfis Esportivos publicos para a Descoberta. Remove o proprio perfil do usuario autenticado, remove perfis ocultos/bloqueados e carrega `sports.sport`, `availabilityWindows` e `teacherProfile` para o payload HTTP.

## Parametros

- `userId`: id do usuario autenticado vindo do auth.
- `filters`: filtros validados pelo `IndexDiscoveryRequest`.

## Filtros

- `sport_id`: filtra por modalidade ativa.
- `sport_slug`: filtra por slug de modalidade ativa.
- `level`: filtra por nivel esportivo.
- `distance_km`: filtra por distancia aproximada entre o perfil autenticado e o candidato.
- `weekday`, `starts_at`, `ends_at`: quando os tres existem, aplica sobreposicao basica de disponibilidade.

## Ranking

Score deterministico antes de IA:

- esporte em comum;
- nivel compativel;
- disponibilidade compativel;
- distancia aproximada;
- completude do perfil.

Cada card inclui `type`, `score`, `reasons`, `distance_km`, `profile` e, quando aplicavel, `teacher_profile`.

## Side effects

Nenhum. Apenas leitura de `sport_profiles`, `availability_windows`, `profile_sports`, `teacher_profiles` e `connections`.
