# DiscoveryService

## discoverForUser

Assinatura: `discoverForUser(int $userId, array $filters = []): array`

Orquestra o modo de Descoberta solicitado por `mode`: `people`, `sessions` ou `places`. Retorna `mode`, uma collection de cards tipados e `empty_state` quando nenhum card foi encontrado.

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
- `goal`: filtra por objetivo esportivo em `profile_sports.goals`.
- `distance_km`: preferencia de proximidade mantida por compatibilidade; distancia aproxima e ordena resultados, mas nao exclui Perfis Esportivos, Professores ou Sessoes publicas.
- `weekday`, `starts_at`, `ends_at`: quando os tres existem, aplica sobreposicao basica de disponibilidade para pessoas e inicio da sessao dentro da janela para sessoes/locais.

## Ranking

Score deterministico antes de IA:

- esporte em comum;
- nivel compativel;
- disponibilidade compativel;
- distancia aproximada;
- completude do perfil.

Cards `person` e `teacher` incluem `type`, `score`, `reasons`, `distance_km`, `profile`, `primary_sport`, `availability_summary`, `location_label`, `recommendation_reason` e, quando aplicavel, `teacher_profile`.

Cards `session` incluem `session`, `host`, `participant_count`, `entry_rule`, modalidade via resumo da sessao e nao incluem preco, chamada de pagamento, capacidade, vagas restantes ou lotacao.

Cards `place` agregam sessoes publicas abertas por local aproximado e incluem esportes disponiveis, contagem de sessoes abertas e proximo horario.

## Side effects

Nenhum. Apenas leitura de `sport_profiles`, `availability_windows`, `profile_sports`, `teacher_profiles`, `connections`, `sport_sessions` e `session_participants`.
