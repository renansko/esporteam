# 02 - Sports And Preferences

Cadastrar modalidades e preferencias esportivas do perfil.

## Acceptance

- [x] Seeder cria esportes iniciais.
- [x] `GET /api/sports` lista modalidades ativas.
- [x] Migration `profile_sports` com esporte, nivel, objetivos e flag principal.
- [x] `PUT /api/profile/sports` substitui preferencias do usuario.
- [x] Validar nivel e objetivos com enums.

## Notes

Esse modulo alimenta discovery, sessoes e professores.

## Verification

- [x] `php -l app/Enums/SportGoal.php`
- [x] `php -l app/Http/Requests/UpdateProfileSportsRequest.php`
- [x] `php -l tests/Feature/Api/SportProfileTest.php`
- [x] `./vendor/bin/pint --test app/Enums/SportGoal.php app/Http/Requests/UpdateProfileSportsRequest.php tests/Feature/Api/SportProfileTest.php`
- [ ] `php artisan test tests/Feature/Api/SportProfileTest.php` bloqueado no ambiente local: PHP nao tem driver SQLite, e Docker Desktop nao esta acessivel pelo WSL.
