# AvailabilityWindow

Disponibilidade recorrente de um Perfil Esportivo. Guarda horarios locais simples para o MVP, sem timezone ou calendario absoluto.

## Campos

- `sport_profile_id`: Perfil Esportivo dono da janela.
- `weekday`: inteiro `0..6`.
- `starts_at`: horario local `HH:mm:ss` no banco, exposto como `HH:mm`.
- `ends_at`: horario local posterior a `starts_at`.

## Relacionamentos

- `profile`: `belongsTo(SportProfile, sport_profile_id)`.

## Regras

- `PUT /api/profile/availability` substitui todas as janelas do Perfil Esportivo autenticado.
- Janelas que apenas encostam (`ends_at == starts_at` da outra) nao contam como sobreposicao.
- Sobreposicao basica: mesmo `weekday`, `starts_at < filtro.ends_at` e `ends_at > filtro.starts_at`.
