# DiscoveryService

Service da Descoberta deterministica inicial. Lista cards de Perfis Esportivos publicos e aplica filtros/ranking sem depender de IA.

## Dependencias

- `SportProfile`: fonte dos perfis candidatos e do perfil autenticado.
- `AvailabilityWindow`: usado via relacionamento para filtro de sobreposicao.
- `Connection`: bloqueios removem perfis da Descoberta.
- `TeacherProfile`: quando existe, o card de descoberta recebe tipo `teacher`.

## Funcoes

- [`profilesForUser`](../functions/DiscoveryService.md#profilesForUser)

## Observacoes

- Exclui o Perfil Esportivo do usuario autenticado quando ele existe.
- Exclui perfis ocultos e perfis bloqueados em qualquer direcao.
- Filtros aceitos: modalidade por `sport_id` ou `sport_slug`, `level`, `distance_km` e janela `weekday`/`starts_at`/`ends_at`.
- Ranking deterministico combina esporte em comum, nivel compativel, disponibilidade, distancia aproximada e completude de perfil.
- Limita o resultado a 50 cards ordenados por score, distancia, nome e id.
