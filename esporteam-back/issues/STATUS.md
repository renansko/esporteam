# Issue Status

Atualizado em 2026-07-07.

## Done

| Issue | Status | Evidence |
| --- | --- | --- |
| 01 - Sport Profile Onboarding | Done | `sport_profiles`, `GET /api/profile`, `PUT /api/profile`, coordenada aproximada no service/resource, testes de isolamento por `user_id`. |
| 02 - Sports And Preferences | Done | Seeder de modalidades, `GET /api/sports`, `profile_sports`, `PUT /api/profile/sports`, enums de nivel e objetivo. |
| 03 - Availability Windows | Done | `availability_windows`, `PUT /api/profile/availability`, validacao de dia/horario e filtro basico de sobreposicao em discovery. |
| 04 - Discovery Feed | Done | `GET /api/discovery` com filtros de modalidade, distancia, nivel e disponibilidade; ranking deterministico; exclusao de perfil proprio, oculto e bloqueado; cards de pessoa/professor; testes de privacidade de localizacao. |
| 05 - Sport Sessions | Done | `sport_sessions`, `session_participants`, `POST /api/sessions`, `GET /api/sessions`, `POST /api/sessions/{id}/join`, regras de capacidade/status e testes HTTP. |
| 06 - Teachers And Classes | Done | `teacher_profiles`, `class_offerings`, `PUT /api/teacher-profile`, `POST /api/classes`, `GET /api/classes`, interesse de aluno em aula e testes HTTP. |
| 07 - Connections And Safety | Done | `connections`, `POST /api/connections`, `PATCH /api/connections/{id}`, amizade/convite, interesse, bloqueio removendo relacionamentos, bloqueados fora da discovery, `reports` e `POST /api/reports` com contexto minimo de moderacao. |
| 08 - Demo Seed Dataset | Done | `DemoSeeder` cria 12 modalidades, 40 perfis esportivos, 8 professores, 15 aulas, 20 sessoes abertas gratuitas, participantes, conexoes, bloqueios e denuncias; teste de idempotencia cobre as contagens. |
| 09 - Discovery Modes And Empty States | Done | `GET /api/discovery` aceita `mode=people|sessions|places`, aplica filtros compartilhados de modalidade, distancia, nivel, objetivo e disponibilidade, retorna cards tipados e inclui `empty_state` com acoes produtivas. |

## Partial

| Issue | Status | Done | Still missing |
| --- | --- | --- | --- |

## Planned

| Issue | Status | Notes |
| --- | --- | --- |
| 10 - Hosted Group Match | Planned | Issue criada; depende de discovery, sessoes e seguranca. |
| 11 - Public Sessions Without Match | Planned | Issue criada; depende de sessoes e seguranca. |
| 12 - Post Match Sport Action | Planned | Issue criada; depende de disponibilidade, sessoes e match em grupo. |
| 13 - Trust And Safety In Discovery | Planned | Issue criada; depende de discovery, sessoes e conexoes/denuncias. |

## Verification Notes

- Docker: `php artisan test` passou em 2026-07-06 com 120 testes e 415 assertions.
- Docker: `php artisan test tests/Feature/Api/SportSessionTest.php` passou em 2026-07-06 com 4 testes e 23 assertions.
- Docker: `php artisan cache:clear && php artisan test` passou em 2026-07-06 com 124 testes e 438 assertions.
- Docker: `php artisan test tests/Feature/Api/ClassOfferingTest.php` passou em 2026-07-06 com 4 testes e 22 assertions.
- Docker: `php artisan test tests/Unit/Security/NewSocialRoutesAuthTest.php` passou em 2026-07-06 com 1 teste e 26 assertions.
- Docker: `php artisan test` passou em 2026-07-06 com 129 testes e 468 assertions.
- Docker: `php artisan test` passou em 2026-07-07 com 131 testes e 489 assertions.
- Docker: `php artisan test tests/Feature/Seeders/DemoSeederTest.php` passou em 2026-07-07 com 1 teste e 22 assertions.
- Docker: `php artisan test` passou em 2026-07-07 com 132 testes e 511 assertions.
- Docker: `php artisan test tests/Feature/Api/DiscoveryTest.php` passou em 2026-07-07 com 8 testes e 68 assertions.
- Docker: `php artisan test` passou em 2026-07-07 com 135 testes e 551 assertions.
