# 12 - Post Match Sport Action

## What to build

Depois de um match ou convite aceito, o produto deve levar a uma acao esportiva concreta: propor horario, escolher local, criar sessao ou confirmar presenca. A conversa pode existir depois, mas o primeiro resultado do match deve ser operacionalizar a pratica.

## Acceptance criteria

- [ ] Um match 1:1 ou em grupo retorna proximas acoes disponiveis de acordo com o contexto.
- [ ] O perfil consegue propor horario usando sua disponibilidade e a disponibilidade dos demais perfis quando existir sobreposicao.
- [ ] O perfil consegue escolher ou sugerir local a partir de locais/sessoes proximas quando houver contexto suficiente.
- [ ] O sistema consegue criar ou vincular uma `SportSession` a partir de um match aceito.
- [ ] O payload explica o motivo da recomendacao ou proxima acao, como `mesmo esporte`, `nivel compativel`, `disponivel sabado` ou `grupo ativo`.
- [ ] Testes cobrem match 1:1, match em grupo e ausencia de disponibilidade em comum.

## Blocked by

- esporteam-back/issues/03-availability-windows.md
- esporteam-back/issues/05-sport-sessions.md
- esporteam-back/issues/10-hosted-group-match.md

