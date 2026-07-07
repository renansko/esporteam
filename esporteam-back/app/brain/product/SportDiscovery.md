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
- Denuncias precisam preservar contexto suficiente para moderacao.
