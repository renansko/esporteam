# 05 - Sport Sessions

Criar e participar de partidas, treinos e encontros.

## Acceptance

- [x] Migration `sport_sessions`.
- [x] Migration `session_participants`.
- [x] `POST /api/sessions` cria sessao.
- [x] `GET /api/sessions` lista sessoes abertas por filtros.
- [x] `POST /api/sessions/{id}/join` registra interesse/participacao.
- [x] Capacidade e status impedem entrada invalida.

## Notes

Tipos iniciais: partida, treino, corrida, aula_aberta e encontro.

## Verification

- [x] `php -l app/Services/SportSessionService.php`
- [x] `php -l app/Http/Controllers/SportSessionController.php`
- [x] `php -l app/Http/Resources/SportSessionResource.php`
- [x] `php -l tests/Feature/Api/SportSessionTest.php`
- [x] `./vendor/bin/pint --test ...`
- [x] Docker: `php artisan test tests/Feature/Api/SportSessionTest.php`
- [x] Docker: `php artisan cache:clear && php artisan test`
