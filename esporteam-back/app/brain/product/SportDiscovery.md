# Sport Discovery

Produto atual: app mobile para descobrir pessoas, professores, aulas e sessoes esportivas proximas.

## Norte

O backend deve continuar seguindo a estrutura existente:

- controllers finos;
- FormRequests para validacao;
- Services para casos de uso;
- Models com invariantes locais;
- Resources definindo shape HTTP;
- `app/brain` como memoria curta e navegavel;
- LLM por interfaces e drivers fake em teste.

## Conceitos

- `SportProfile`: identidade esportiva publica do usuario.
- `Sport`: modalidade.
- `ProfileSport`: relacao perfil/modalidade com nivel e objetivos.
- `AvailabilityWindow`: janelas de disponibilidade.
- `SportSession`: partida, treino, corrida, aula aberta ou encontro gratuito para participantes.
- `TeacherProfile`: perfil profissional.
- `ClassOffering`: aula individual ou em grupo.
- `Connection`: convite, amizade, interesse, bloqueio.
- `Report`: denuncia/moderacao.
- `ProfileSubscription`: assinatura futura de plataforma para organizadores ou entusiastas, confirmada por billing externo; nao e preco de sessao.

## Escopo de descoberta

A Descoberta de perfis, sessoes e locais e global entre Perfis Esportivos, nao multi-tenant por workspace. Organizadores podem criar muitas sessoes e tambem participar de sessoes de outros organizadores; entusiastas podem descobrir qualquer sessao publica compativel por match, seguranca e regras internas da sessao.

O projeto `esporteam-workspace` fica reservado para fronteiras administrativas futuras. Ele nao deve limitar hoje Descoberta, Match de Sessao, convites ou participacao esportiva.

## Sessoes publicas

Sessoes publicas podem ter entrada por convite, entrada direta ou entrada com aprovacao. `GET /api/sessions` pode filtrar por modalidade, distancia, nivel, horario e disponibilidade interna de vagas, mas o payload publico comunica apenas a proxima acao (`entrar`, `pedir_vaga` ou `indisponivel`) e a contagem total de participantes.

## Matching inicial

Score deterministico antes de IA:

- esporte em comum;
- distancia aproximada;
- nivel compativel;
- disponibilidade sobreposta;
- intencao compativel, como jogar, treinar, aprender ou competir;
- penalidade para perfis incompletos;
- exclusao total quando existe bloqueio.

IA entra depois para melhorar ranking e explicar recomendacoes, mas nunca deve ser requisito para a tela principal funcionar.

## Privacidade

- Descoberta usa localizacao aproximada.
- Coordenada precisa nao aparece em payload publico.
- Perfil pode sair da descoberta.
- Bloqueios removem ambos os lados da descoberta.
- Cards publicos de sessao podem mostrar a contagem total de participantes, mas nao expõem capacidade, vagas restantes ou status de lotacao antes do match/acao.
- Denuncias preservam contexto minimo de moderacao: reporter/reported com ids, `user_id`, nome, cidade, regiao e visibilidade no momento do reporte.
