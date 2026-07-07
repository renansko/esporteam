# 10 - Hosted Group Match

## What to build

Permitir que uma sessao esportiva tenha um anfitriao que monta um grupo a partir de perfis compativeis. O anfitriao deve conseguir ver recomendacoes para a sessao, convidar perfis e aprovar interessados, formando um match em grupo em vez de limitar a experiencia a match 1:1.

## Acceptance criteria

- [x] Uma sessao registra o anfitriao e diferencia participantes convidados, interessados, aprovados, recusados e removidos.
- [x] O anfitriao consegue listar perfis recomendados para a sessao por modalidade, distancia, nivel, objetivo e disponibilidade.
- [x] O anfitriao consegue convidar um ou mais perfis recomendados para participar da sessao.
- [x] Um perfil convidado consegue aceitar ou recusar o convite da sessao.
- [x] O anfitriao consegue aprovar ou recusar pedidos de vaga quando a sessao exigir aprovacao.
- [x] Capacidade, bloqueios e visibilidade impedem convite ou aprovacao invalida.
- [x] Match em grupo nao introduz cobranca por vaga; a sessao hospedada continua gratuita para participantes.
- [x] Testes cobrem match em grupo, transicoes de status e regras de seguranca.

## Blocked by

- esporteam-back/issues/04-discovery-feed.md
- esporteam-back/issues/05-sport-sessions.md
- esporteam-back/issues/07-connections-safety.md
