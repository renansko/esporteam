# 13 - Trust And Safety In Discovery

## What to build

Adicionar sinais de confianca e controles de seguranca nos cards de descoberta, convites e sessoes. Antes de aceitar convite, pedir vaga ou entrar em sessao publica, o perfil esportivo deve ver informacoes suficientes para decidir com seguranca sem expor localizacao precisa de pessoas.

## Acceptance criteria

- [x] Cards de pessoa exibem foto/nome publico, modalidade, nivel, bairro ou distancia aproximada, disponibilidade e selo de perfil completo quando aplicavel.
- [x] Cards de sessao exibem anfitriao, regra de entrada, local aproximado, horario, vagas/status, modalidade e participantes aprovados quando permitido.
- [x] Cards de sessao nao exibem preco de participacao; qualquer assinatura do anfitriao e sinal de plataforma, nao cobranca do evento.
- [x] Bloquear e denunciar estao disponiveis a partir de perfis, convites e sessoes.
- [x] Bloqueios removem ambas as partes de discovery, recomendacoes de anfitriao, convites e sessoes privadas.
- [x] Coordenadas precisas de perfis nunca aparecem em payload publico.
- [x] Testes cobrem bloqueio, denuncia, ocultacao de coordenadas e remocao de descoberta.

## Verification

- [x] `php -l` nos resources/services alterados.
- [x] `./vendor/bin/pint --test ...`
- [x] `php artisan route:list --path=api`
- [x] `git diff --check`
- [ ] Docker: `php artisan test tests/Feature/Api/DiscoveryTest.php` bloqueado por erro WSL/vsock ao acessar Docker.

## Blocked by

- esporteam-back/issues/04-discovery-feed.md
- esporteam-back/issues/05-sport-sessions.md
- esporteam-back/issues/07-connections-safety.md
