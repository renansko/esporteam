# 11 - Public Sessions Without Match

## What to build

Permitir que o anfitriao crie uma sessao publica em que perfis elegiveis possam entrar ou pedir vaga sem match previo com o anfitriao. Esse fluxo reduz atrito para iniciantes e transforma a descoberta em participacao direta quando a atividade estiver aberta.

## Acceptance criteria

- [ ] `POST /api/sessions` permite escolher modo de entrada: convite, publica com entrada direta ou publica com aprovacao.
- [ ] `GET /api/sessions` lista sessoes publicas por modalidade, distancia, nivel, horario e disponibilidade de vagas.
- [ ] Um perfil elegivel consegue entrar diretamente em uma sessao publica com entrada direta.
- [ ] Um perfil elegivel consegue pedir vaga em uma sessao publica com aprovacao do anfitriao.
- [ ] Sessoes publicas respeitam capacidade, status, bloqueios, faixa de nivel e visibilidade.
- [ ] O payload da sessao comunica claramente se a proxima acao e `entrar`, `pedir vaga` ou `indisponivel`.
- [ ] Sessoes publicas nao aceitam preco, taxa, ingresso ou pagamento obrigatorio.
- [ ] Testes cobrem entrada direta, pedido de vaga, capacidade cheia e bloqueios.

## Blocked by

- esporteam-back/issues/05-sport-sessions.md
- esporteam-back/issues/07-connections-safety.md
