# SportSessionSeries

Identidade duradoura de uma regra semanal publicada por um Anfitriao da Sessao.

## Campos e invariantes

- `starts_on`, `starts_at_local`, `timezone`, `duration_minutes`, `interval_weeks` e `weekdays` definem o horario de parede.
- Termino e `never`, `date` (`ends_on`) ou `count` (`occurrence_count`).
- `publication_key` e unica por anfitriao; `occurrence_key` e unica por serie na ocorrencia materializada.
- Localizacao exata permanece na serie e em cada ocorrencia; Descoberta usa somente os valores aproximados.

## Relacionamentos

- `creator`: Anfitriao da Sessao (`SportProfile`).
- `sport`: Modalidade.
- `occurrences`: Sessoes Esportivas materializadas.
