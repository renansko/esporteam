# DiscoveryService

Service da Descoberta deterministica inicial. Hoje lista Perfis Esportivos publicos e aplica filtros basicos sem depender de IA.

## Dependencias

- `SportProfile`: fonte dos perfis candidatos e do perfil autenticado.
- `AvailabilityWindow`: usado via relacionamento para filtro de sobreposicao.

## Funcoes

- [`profilesForUser`](../functions/DiscoveryService.md#profilesForUser)

## Observacoes

- Exclui o Perfil Esportivo do usuario autenticado quando ele existe.
- Limita o resultado a 50 perfis, ordenados por `display_name` e `id`.
- Ranking amplo, distancia, esporte comum e bloqueios ficam para cortes posteriores da Descoberta.
