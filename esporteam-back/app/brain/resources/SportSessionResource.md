# SportSessionResource

Shape HTTP de Sessao Esportiva.

## Campos

- `id`
- `creator_profile_id`
- `sport_id`
- `title`
- `description`
- `type`
- `starts_at`
- `ends_at`, `timezone`, `rules`, `equipment`
- `location_label`
- `city`
- `region`
- `location.latitude_approx`
- `location.longitude_approx`
- `meeting_point` somente para Anfitrião da Sessão e participação `joined`/`approved`; pedidos pendentes e público recebem apenas a área aproximada.
- `capacity`
- `visibility`
- `status`
- `participant_count`
- `creator` quando carregado
- `sport` quando carregado
- `participants` quando carregado
- `created_at`
- `updated_at`
