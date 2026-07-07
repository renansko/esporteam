# 06 - Teachers And Classes

Permitir professores e aulas dentro do marketplace local.

## Acceptance

- [x] Migration `teacher_profiles`.
- [x] Migration `class_offerings`.
- [x] `PUT /api/teacher-profile` cria/atualiza perfil profissional.
- [x] `POST /api/classes` cria aula.
- [x] `GET /api/classes` lista por esporte, distancia, preco e horario.
- [x] Aluno consegue registrar interesse em aula.

## Notes

Pagamento e checkout ficam fora do MVP.

Preco em `class_offerings` representa aula/oferta profissional. Isso nao deve ser reaproveitado para transformar `sport_sessions` em eventos pagos. Organizadores e entusiastas podem ter assinatura da plataforma em billing separado, mas a participacao em sessao esportiva continua gratuita.

## Verification

- [x] `php -l app/Services/ClassOfferingService.php`
- [x] `php -l app/Http/Controllers/ClassOfferingController.php`
- [x] `php -l app/Http/Resources/ClassOfferingResource.php`
- [x] `php -l app/Models/ClassOffering.php`
- [x] `php -l app/Models/ClassInterest.php`
- [x] `php -l tests/Feature/Api/ClassOfferingTest.php`
- [x] `./vendor/bin/pint --test ...`
- [x] Docker: `php artisan test tests/Unit/Security/NewSocialRoutesAuthTest.php`
- [x] Docker: `php artisan test tests/Feature/Api/ClassOfferingTest.php`
- [x] Docker: `php artisan test`
