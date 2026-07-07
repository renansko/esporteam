# 12 - Post Match Sport Action

## What to build

Depois de um match ou convite aceito, o produto deve levar a uma acao esportiva concreta: propor horario, escolher local, criar sessao ou confirmar presenca. A conversa pode existir depois, mas o primeiro resultado do match deve ser operacionalizar a pratica.

## Acceptance criteria

- [x] Um match 1:1 ou em grupo retorna proximas acoes disponiveis de acordo com o contexto.
- [x] O perfil consegue propor horario usando sua disponibilidade e a disponibilidade dos demais perfis quando existir sobreposicao.
- [x] O perfil consegue escolher ou sugerir local a partir de locais/sessoes proximas quando houver contexto suficiente.
- [x] O sistema consegue criar ou vincular uma `SportSession` a partir de um match aceito.
- [x] Criar ou vincular `SportSession` a partir de match nao cria cobranca para participantes.
- [x] O payload explica o motivo da recomendacao ou proxima acao, como `mesmo esporte`, `nivel compativel`, `disponivel sabado` ou `grupo ativo`.
- [x] Testes cobrem match 1:1, match em grupo e ausencia de disponibilidade em comum.

## Notes

- `GET /api/post-match-actions` aceita `connection_id` para match 1:1 aceito ou `session_id` para match em grupo ativo.
- `POST /api/post-match-actions/session` cria sessao privada a partir de uma conexao aceita, ou atualiza/vincula a sessao existente do grupo com horario e local escolhidos.
- Sessoes criadas por post-match usam `entry_mode=convite`, `visibility=private`, incluem o outro perfil como `approved` e rejeitam campos de cobranca.

## Verification

- [x] `php -l app/Services/PostMatchSportActionService.php`
- [x] `./vendor/bin/pint --test app/Services/PostMatchSportActionService.php app/Http/Controllers/PostMatchSportActionController.php app/Http/Requests/IndexPostMatchSportActionRequest.php app/Http/Requests/StorePostMatchSportActionSessionRequest.php app/Http/Resources/PostMatchSportActionResource.php routes/api.php tests/Feature/Api/PostMatchSportActionTest.php tests/Unit/Security/NewSocialRoutesAuthTest.php`
- [x] Docker: `php artisan test tests/Feature/Api/PostMatchSportActionTest.php`
- [x] Docker: `php artisan test tests/Feature/Api/SportSessionTest.php`
- [x] Docker: `php artisan test tests/Unit/Security/NewSocialRoutesAuthTest.php`
- [x] Docker: `php artisan test`

## Blocked by

- esporteam-back/issues/03-availability-windows.md
- esporteam-back/issues/05-sport-sessions.md
- esporteam-back/issues/10-hosted-group-match.md
