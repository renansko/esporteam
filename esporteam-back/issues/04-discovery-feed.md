# 04 - Discovery Feed

Listar pessoas proximas e relevantes para o usuario.

## Acceptance

- [x] `GET /api/discovery` aceita filtros de esporte, distancia, nivel e horario.
- [x] Ranking deterministico: esporte comum, distancia, nivel, disponibilidade e perfil completo.
- [x] Perfis invisiveis ou bloqueados nao aparecem.
- [x] Payload diferencia pessoa, professor e sessao quando aplicavel.
- [x] Testes cobrem privacidade de localizacao.

## Notes

IA nao deve ser dependencia para a primeira tela funcionar.

## Verification

- [x] `php -l app/Services/DiscoveryService.php`
- [x] `php -l app/Http/Requests/IndexDiscoveryRequest.php`
- [x] `php -l app/Http/Resources/DiscoveryCardResource.php`
- [x] `php -l app/Http/Controllers/DiscoveryController.php`
- [x] `php -l tests/Feature/Api/DiscoveryTest.php`
- [x] `./vendor/bin/pint --test app/Services/DiscoveryService.php app/Http/Requests/IndexDiscoveryRequest.php app/Http/Resources/DiscoveryCardResource.php app/Http/Controllers/DiscoveryController.php tests/Feature/Api/DiscoveryTest.php`
- [x] Docker: `php artisan test tests/Feature/Api/DiscoveryTest.php`
- [x] Docker: `php artisan test`
