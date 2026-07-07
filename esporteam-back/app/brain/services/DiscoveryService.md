# DiscoveryService

Service da Descoberta deterministica inicial. Lista cards tipados de Perfis Esportivos, Sessoes Esportivas e Locais derivados de sessoes publicas abertas, aplicando filtros/ranking sem depender de IA.

## Dependencias

- `SportProfile`: fonte dos perfis candidatos e do perfil autenticado.
- `AvailabilityWindow`: usado via relacionamento para filtro de sobreposicao.
- `Connection`: bloqueios removem perfis da Descoberta.
- `TeacherProfile`: quando existe, o card de descoberta recebe tipo `teacher`.
- `SportSession`: fonte dos cards `session` e dos cards `place` agregados por local aproximado.

## Funcoes

- [`discoverForUser`](../functions/DiscoveryService.md#discoverForUser)
- [`profilesForUser`](../functions/DiscoveryService.md#profilesForUser)

## Observacoes

- `GET /api/discovery` aceita `mode=people|sessions|places`; ausencia de modo preserva `people`.
- Exclui o Perfil Esportivo do usuario autenticado quando ele existe.
- Exclui perfis ocultos e perfis bloqueados em qualquer direcao.
- Filtros aceitos: modalidade por `sport_id` ou `sport_slug`, `level`, `goal`, `distance_km` e janela `weekday`/`starts_at`/`ends_at`.
- Ranking deterministico combina esporte em comum, nivel compativel, disponibilidade, distancia aproximada e completude de perfil.
- Estados vazios retornam `empty_state` com sugestoes como ampliar distancia, remover nivel e criar sessao publica.
- Cards publicos de sessao retornam contagem total de participantes e regra de entrada por match/aprovacao, mas nao expõem capacidade, vagas restantes ou lotacao.
- Limita o resultado a 50 cards ordenados por score, distancia, nome/horario e id.
