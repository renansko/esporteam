# 01 - Sport Profile Onboarding

Criar o perfil esportivo do usuario autenticado.

## Acceptance

- [x] Migration `sport_profiles` com `user_id`, nome publico, bio, cidade/regiao, localizacao aproximada, visibilidade e avatar.
- [x] `GET /api/profile` retorna perfil atual ou estado vazio.
- [x] `PUT /api/profile` cria/atualiza perfil do usuario.
- [x] Resource nunca expoe coordenada precisa.
- [x] Teste garante isolamento por `user_id`.

## Notes

Primeiro corte deve ser simples. Nada de matching antes do perfil existir.

## Verification

- [x] `php -l app/Services/SportProfileService.php`
- [x] `php -l tests/Feature/Api/SportProfileTest.php`
- [x] `./vendor/bin/pint --test app/Services/SportProfileService.php tests/Feature/Api/SportProfileTest.php`
- [ ] `php artisan test tests/Feature/Api/SportProfileTest.php` bloqueado no ambiente local: PHP nao tem driver SQLite, e Docker Desktop nao esta acessivel pelo WSL.
