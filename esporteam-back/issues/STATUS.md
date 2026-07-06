# Issue Status

Atualizado em 2026-07-06.

## Done

| Issue | Status | Evidence |
| --- | --- | --- |
| 01 - Sport Profile Onboarding | Done | `sport_profiles`, `GET /api/profile`, `PUT /api/profile`, coordenada aproximada no service/resource, testes de isolamento por `user_id`. |
| 02 - Sports And Preferences | Done | Seeder de modalidades, `GET /api/sports`, `profile_sports`, `PUT /api/profile/sports`, enums de nivel e objetivo. |
| 03 - Availability Windows | Done | `availability_windows`, `PUT /api/profile/availability`, validacao de dia/horario e filtro basico de sobreposicao em discovery. |

## Partial

| Issue | Status | Done | Still missing |
| --- | --- | --- | --- |
| 04 - Discovery Feed | Partial | `GET /api/discovery` existe com filtro de disponibilidade e remove o proprio perfil. | Filtros de modalidade/distancia/nivel, ranking deterministico completo, exclusao por bloqueio, payload diferenciado para professor/sessao e testes de privacidade de localizacao. |
| 06 - Teachers And Classes | Partial | `teacher_profiles`, `PUT /api/teacher-profile` e relacao professor/aluno existem. | `class_offerings`, `POST /api/classes`, `GET /api/classes` e fluxo de interesse em aula. |
| 07 - Connections And Safety | Partial | `connections`, `POST /api/connections`, `PATCH /api/connections/{id}` e bloqueio removendo amizade existem. | `reports`, `POST /api/reports` e remocao de discovery baseada em bloqueios. |
| 08 - Demo Seed Dataset | Partial | Seeder cria 12 modalidades iniciais. | 40 perfis, 8 professores, 15 aulas, 20 sessoes abertas, convites, participantes, bloqueios e denuncias. |

## Planned

| Issue | Status | Notes |
| --- | --- | --- |
| 05 - Sport Sessions | Planned | Ainda nao ha `sport_sessions`, `session_participants` ou endpoints `/api/sessions`. |
| 09 - Discovery Modes And Empty States | Planned | Issue criada a partir da pesquisa; depende de discovery mais completo. |
| 10 - Hosted Group Match | Planned | Issue criada; depende de discovery, sessoes e seguranca. |
| 11 - Public Sessions Without Match | Planned | Issue criada; depende de sessoes e seguranca. |
| 12 - Post Match Sport Action | Planned | Issue criada; depende de disponibilidade, sessoes e match em grupo. |
| 13 - Trust And Safety In Discovery | Planned | Issue criada; depende de discovery, sessoes e conexoes/denuncias. |

## Verification Notes

- Testes de feature nao rodam neste ambiente local porque o PHP disponivel nao tem driver SQLite, enquanto `phpunit.xml` usa `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:`.
- Docker Desktop tambem nao esta acessivel pelo WSL neste momento, entao a suite precisa ser rodada em ambiente com `pdo_sqlite` ou container disponivel.
