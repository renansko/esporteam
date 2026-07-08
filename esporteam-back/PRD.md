# PRD - Esporteam Mobile (MVP)

> App mobile para encontrar pessoas, professores, aulas e grupos praticando esportes perto de voce.

**Status:** Draft pivot
**Stack:** Laravel API + auth/workspace services + mobile app
**Identidade:** JWT RS256 emitido pelo `esporteam-auth`; perfis e recursos do app vivem no `esporteam-back`
**Principio tecnico:** manter a estrutura atual do backend, incluindo `app/brain`, camadas Service/Model/Resource/Request e o hub de LLM.

## Problema

Quem quer praticar esporte depende de grupos soltos em WhatsApp, indicacoes informais e buscas manuais. A pessoa nao sabe quem joga perto, quais professores estao disponiveis, onde existem aulas abertas, ou se algum amigo quer treinar hoje.

Professores e organizadores tambem sofrem: precisam divulgar aulas, confirmar presenca, responder perguntas repetidas e preencher turmas sem uma vitrine local confiavel.

## Solucao

Um app mobile de descoberta esportiva local:

- encontrar pessoas proximas que praticam os mesmos esportes;
- encontrar professores, personal trainers, tecnicos e aulas;
- criar ou entrar em partidas, treinos e grupos;
- convidar amigos;
- receber recomendacoes por proximidade, esporte, horario, nivel e objetivo;
- usar IA para melhorar matching, onboarding e sugestoes.

## Publico

- praticantes casuais que querem companhia para jogar ou treinar;
- atletas amadores procurando grupos recorrentes;
- professores e tecnicos oferecendo aulas;
- organizadores de arenas, quadras, clubes e comunidades;
- amigos que querem combinar atividades com menos atrito.

## Vocabulario

- **Perfil esportivo:** perfil publico do usuario dentro do app, com cidade/bairro aproximado, esportes, nivel, objetivos e disponibilidade.
- **Esporte:** modalidade praticada, como futebol, corrida, tenis, beach tennis, musculacao, volei, basquete, ciclismo ou luta.
- **Sessao esportiva:** evento pontual ou recorrente: partida, treino, corrida em grupo, aula aberta ou encontro.
- **Professor:** usuario com perfil profissional, modalidades, preco, locais de atendimento e horarios.
- **Organizador:** perfil esportivo que cria sessoes, grupos ou atividades locais. Pode ter assinatura da plataforma, mas nao cobra participantes pela sessao esportiva.
- **Assinatura da plataforma:** relacao comercial do perfil/organizador com o Esporteam, possivelmente confirmada no futuro por microservico de pagamentos. Nao representa taxa, ingresso ou preco de uma sessao esportiva.
- **Aula:** oferta criada por professor ou organizador, individual ou em grupo.
- **Conexao:** relacao entre usuarios: amigo, interesse, convite, bloqueio ou match.
- **Descoberta:** feed/lista/mapa de pessoas, aulas e sessoes proximas.
- **Workspace:** fica como fronteira tecnica herdada para auth/membership/admin; nao deve contaminar o dominio mobile com linguagem B2B.

## MVP

O MVP precisa provar o loop principal:

1. usuario cria perfil esportivo;
2. escolhe esportes, nivel e disponibilidade;
3. ve pessoas, aulas e sessoes proximas;
4. envia convite ou entra em uma sessao;
5. professor cria uma aula e recebe interessados;
6. app recomenda matches melhores conforme uso.

## User Stories

1. Como praticante, quero escolher meus esportes, nivel e disponibilidade, para receber recomendacoes relevantes.
2. Como praticante, quero ver pessoas proximas que praticam o mesmo esporte, para chamar alguem para jogar ou treinar.
3. Como praticante, quero filtrar por distancia, esporte, nivel e horario, para encontrar opcoes que realmente servem para mim.
4. Como praticante, quero criar uma sessao esportiva, para convidar pessoas para uma partida, treino ou corrida.
5. Como praticante, quero entrar em uma sessao aberta, para participar sem depender de grupos externos.
6. Como praticante, quero convidar amigos para uma atividade, para combinar esporte rapidamente.
7. Como professor, quero criar meu perfil profissional, para aparecer nas buscas.
8. Como professor, quero cadastrar aulas com preco, local, horario e vagas, para receber interessados.
9. Como aluno, quero encontrar professores proximos por modalidade, preco e avaliacao, para marcar uma aula.
10. Como usuario, quero bloquear ou denunciar pessoas, para manter o ambiente seguro.
11. Como usuario, quero controlar minha visibilidade de localizacao, para nao expor meu endereco exato.
12. Como operador/admin, quero moderar perfis, sessoes e denuncias, para proteger a comunidade.

## Decisoes De Produto

- Localizacao deve ser aproximada por padrao. Nunca expor coordenada exata de residencia.
- Matching inicial combina distancia, esportes em comum, nivel, disponibilidade e tipo de intencao.
- Professores e aulas devem coexistir com praticantes comuns no mesmo app, mas com filtros e cards distintos.
- Sessoes esportivas sao gratuitas para participantes. Instrutores, organizadores e entusiastas podem ter assinatura da plataforma, mas essa assinatura nao transforma a sessao em evento pago.
- Preco pertence a perfil profissional/aula (`teacher_profiles`, `class_offerings`), nao a `sport_sessions`.
- Chat pode entrar depois do MVP; no primeiro corte, convite/interesse com status ja valida demanda.
- Checkout, reserva de quadra e cobranca por participante ficam fora do MVP.
- Assinaturas de organizadores/entusiastas podem ser modeladas depois em tabela propria e confirmadas por microservico de pagamentos.
- O backend pode reaproveitar o hub de LLM para embeddings, recomendacao e explicacao de matches.

## Modelo De Dados Proposto

Primeiro corte, preservando a separacao por camadas:

- `sport_profiles`: user_id, display_name, bio, city, region, latitude_approx, longitude_approx, visibility, avatar_url.
- `sports`: name, slug, category.
- `profile_sports`: profile_id, sport_id, level, goals, preferred_positions, is_primary.
- `availability_windows`: profile_id, weekday, starts_at, ends_at.
- `teacher_profiles`: profile_id, headline, credentials, hourly_price_cents, service_radius_km, verified_at.
- `class_offerings`: teacher_profile_id, sport_id, title, description, price_cents, location_label, starts_at, recurrence, capacity.
- `sport_sessions`: creator_profile_id, sport_id, title, description, type, starts_at, location_label, latitude_approx, longitude_approx, capacity, visibility, status.
- `session_participants`: session_id, profile_id, status.
- `connections`: requester_profile_id, target_profile_id, type, status.
- `reports`: reporter_profile_id, reported_profile_id, reason, details, status.

Futuro billing de plataforma:

- `profile_subscriptions`: profile_id, plan, status, external_subscription_id, current_period_ends_at, verified_at.
- A fonte de verdade de pagamento deve vir do microservico de pagamentos; o backend de esporte guarda apenas estado necessario para autorizacao e descoberta.
- Nenhum campo de preco deve ser adicionado a `sport_sessions`.

## API Proposta

Autenticado:

- `GET /api/me`
- `GET /api/sports`
- `GET /api/profile`
- `PUT /api/profile`
- `PUT /api/profile/sports`
- `PUT /api/profile/availability`
- `GET /api/discovery`
- `GET /api/profiles/{id}`
- `POST /api/connections`
- `PATCH /api/connections/{id}`
- `POST /api/sessions`
- `GET /api/sessions`
- `GET /api/sessions/{id}`
- `POST /api/sessions/{id}/join`
- `PATCH /api/sessions/{id}/participants/{profileId}`
- `PUT /api/teacher-profile`
- `POST /api/classes`
- `GET /api/classes`
- `POST /api/reports`

Admin:

- `GET /api/admin/reports`
- `PATCH /api/admin/reports/{id}`
- `PATCH /api/admin/profiles/{id}/status`

## SPEC Consolidada Para Front MVP Participante

Esta SPEC consolida o contrato do backend que deve sustentar o PRD de front `docs/prd/frontend-mvp-participante.md` e as issues front #2 a #10. O recorte e o Modo Participante do Entusiasta; fluxos completos de Anfitriao, aprovacao operacional do anfitriao e professor/aulas continuam no PRD amplo, mas nao bloqueiam o primeiro front participante.

### Assinatura De Dominio

- Autenticacao identifica um `User`; Descoberta, Partidas e Perfil do app usam sempre o `SportProfile` ativo do usuario autenticado.
- Descoberta e participacao sao globais entre Perfis Esportivos publicos, nao escopadas por Workspace.
- A UI do front deve falar em `Perfil Esportivo`, `Entusiasta`, `Sessao Esportiva`, `Modalidade`, `Anfitriao da Sessao`, `Descoberta`, `Partidas` e `Disponibilidade`.
- `sport_sessions` nunca recebe preco, taxa, moeda ou pagamento. Preco pertence a `teacher_profiles` e `class_offerings`; assinatura de plataforma futura pertence a `profile_subscriptions`.
- Localizacao publica e aproximada: payloads publicos podem expor cidade, regiao, `location_label_public`, distancia aproximada e coordenadas aproximadas quando existirem, mas nao endereco residencial preciso.
- Capacidade e vagas restantes nao aparecem em cards publicos antes de match/acao. O backend pode expor `participant_count`, `next_action`, `entry_rule` e `vacancy_status`, mas nao deve expor `capacity` para quem nao e anfitriao.

### Modos De Entrada De Sessao

`entry_mode` e a fonte de verdade para a acao primaria:

- `publica_direta`: sessao aberta; `POST /api/sessions/{id}/join` cria participacao `joined`; front exibe `Vou participar` ou `Tenho interesse` com resultado confirmado.
- `publica_aprovacao`: sessao com curadoria; `POST /api/sessions/{id}/join` cria participacao `interested`; front exibe `Pedir para participar` e estado `Aguardando aprovacao`.
- `convite`: sessao por convite; nao aparece como entrada publica acionavel; `next_action` deve ser `indisponivel`.

O campo legado `requires_approval` pode continuar no payload por compatibilidade, mas o front deve preferir `entry_mode`/`next_action` para decidir comportamento.

### Estados De Participacao

O backend persiste `session_participants.status`; o front normaliza para estados de Partidas:

- `joined` e `approved`: `Confirmado`.
- `interested` e `invited`: `Aguardando`.
- `declined` e `removed`: `Recusado` ou removido pelo anfitriao, conforme copy da tela.
- `left`: fora da primeira interface de Partidas, mas deve ser tratado como historico nao-confirmado quando aparecer.

Acoes duplicadas devem falhar de forma previsivel: se o Perfil Esportivo ja tem `joined`, `approved` ou `interested` para a sessao, `POST /api/sessions/{id}/join` retorna validacao e o front preserva o estado atual.

### Contrato De Endpoints Para As Issues Do Front

#### Perfil Esportivo (#10)

- `GET /api/profile`: retorna o `SportProfile` ativo ou `data = null` quando ainda nao existe.
- `PUT /api/profile`: cria/atualiza identidade esportiva publica sem alterar dados de autenticacao do `User`.
- `PUT /api/profile/sports`: substitui Modalidades, Nivel Esportivo, Objetivos Esportivos, posicoes e modalidade primaria.
- `PUT /api/profile/availability`: substitui janelas de Disponibilidade.
- O payload normalizado deve incluir `id`, `display_name`, `bio`, `city`, `region`, `location.latitude_approx`, `location.longitude_approx`, `visibility`, `avatar_url`, `sports[]` e `availability[]`.

#### Descobrir Deck E Filtros (#3, #4, #5)

- `GET /api/discovery?mode=sessions` e o feed principal para cards compativeis de Sessao Esportiva.
- Filtros aceitos: `sport_id`, `sport_slug`, `level`, `goal`, `distance_km`, `weekday`, `starts_at`, `ends_at`.
- Cada card de sessao deve expor `type=session`, `score`, `reasons`, `distance_km`, `recommendation_reason`, `entry_rule`, `participant_count`, `vacancy_status`, `safety_actions`, `host` e `session`.
- `session` deve trazer `id`, `creator_profile_id`, `sport_id`, `title`, `description`, `type`, `starts_at`, `location_label`, `city`, `region`, `location_label_public`, `requires_approval`, `entry_mode`, `min_level`, `max_level`, `visibility`, `status`, `participant_count`, `sport` e uma amostra de `approved_participants`.
- `entry_rule=approval_required` mapeia para UI curada; `entry_rule=match_required` com `entry_mode=publica_direta` mapeia para UI aberta.
- `POST /api/sessions/{id}/join` e a acao do botao `Tenho interesse`; o resultado depende de `entry_mode`.
- `Pular` e `Voltar` permanecem locais no front neste recorte; nao ha contrato de persistencia de dismiss/undo.

#### Mapa E Lista (#8)

- `GET /api/discovery?mode=sessions` pode alimentar Mapa e Lista quando a UI quer cards ranqueados por compatibilidade.
- `GET /api/sessions` pode alimentar uma lista temporal/geografica de sessoes abertas e publicas.
- Filtros aceitos em `GET /api/sessions`: `sport_id`, `sport_slug`, `type`, `entry_mode`, `level`, `distance_km`, `weekday`, `starts_at`, `ends_at`, `has_available_slots`, `city`, `region`, `starts_after`, `starts_before`.
- O front pode usar um mapa deterministico no MVP; se nao houver coordenadas, deve cair para Lista/bottom sheet com `location_label_public`, cidade, regiao e distancia quando disponivel.

#### Detalhe Aberto E Curado (#6, #7)

- `GET /api/sessions/{id}` e requisito da SPEC para a tela de detalhe. O PRD ja lista este endpoint, mas a rota ainda precisa existir no backend.
- O detalhe deve retornar `SportSessionResource` com `creator`, `sport`, `participant_count`, `participants` quando permitido, `participation` do perfil autenticado quando existir, `entry_mode`, `requires_approval`, `next_action` e dados de localizacao publica.
- Para `publica_direta`, `POST /api/sessions/{id}/join` retorna `201` com sessao atualizada e participacao `joined`.
- Para `publica_aprovacao`, `POST /api/sessions/{id}/join` retorna `201` com sessao atualizada e participacao `interested`.
- Erros de elegibilidade, bloqueio, capacidade, visibilidade ou duplicidade retornam validacao; o front deve manter o detalhe aberto e mostrar feedback.

#### Partidas (#9)

- A primeira SPEC pode derivar Partidas do `GET /api/sessions` somente se o backend carregar a participacao do Perfil Esportivo autenticado; o contrato mais claro e adicionar endpoint dedicado `GET /api/profile/sessions` ou filtro `GET /api/sessions?participating=true`.
- O payload precisa listar sessoes em que o Perfil Esportivo atuou, incluindo status de participacao normalizado, dados basicos da Sessao Esportiva e linkavel para `GET /api/sessions/{id}`.
- Itens `declined`/`removed` nao devem desaparecer silenciosamente; o backend deve permitir retorno historico suficiente para a aba Recusado.

### Gaps Que O Back Precisa Fechar Para Ficar 100% Consistente

- Implementar `GET /api/sessions/{id}` com autorizacao publica segura e `SportSessionResource` enriquecido para detalhe.
- Definir endpoint ou filtro dedicado para Partidas do Perfil Esportivo ativo.
- Garantir que `POST /api/sessions/{id}/join` sempre retorne a participacao do perfil autenticado carregada no recurso, para o front atualizar Descobrir e Partidas sem segunda chamada.
- Revisar `GET /api/discovery?mode=sessions` para nao expor `vacancy_status` como capacidade reversivel antes do match; se ele indicar lotacao, deve continuar sem revelar quantidade de vagas restantes.
- Manter testes de feature cobrindo aberta direta, curada pendente, duplicidade, bloqueio, perfil oculto, faixa de nivel, capacidade e payload sem `capacity` para nao-anfitriao.

## IA E Brain

O `app/brain` permanece como memoria tecnica do backend.

Usos de IA que fazem sentido para o novo produto:

- gerar embeddings de bio, esportes, objetivos e preferencias;
- ranquear discovery com explicacao curta;
- detectar perfis ou mensagens suspeitas;
- sugerir sessoes, aulas e grupos com base no historico;
- normalizar esportes escritos livremente no onboarding;
- ajudar professor a criar descricao de aula.

O clustering atual do projeto antigo nao deve ser removido sem uma migracao planejada. Ele vira referencia tecnica para o novo matching/recommendation pipeline: execucoes auditaveis, fallback deterministico, custo controlado e testes com fake LLM.

## Regras De Seguranca

- Coordenadas precisas ficam privadas; listagens usam distancia aproximada.
- Usuario pode ocultar perfil da descoberta.
- Bloqueio impede descoberta, convite e interacao futura entre as partes.
- Denuncia deve congelar o contexto minimo para moderacao.
- Dados de professor verificado precisam ter trilha de auditoria.

## Fora Do MVP

- pagamento in-app;
- reserva de quadras;
- chat realtime completo;
- ranking competitivo;
- integracao com wearables;
- assinatura premium;
- agenda externa Google/Apple;
- marketplace de produtos esportivos.

## Seeds De Demo

- 40 perfis de praticantes em uma cidade;
- 12 esportes;
- 8 professores;
- 15 aulas;
- 20 sessoes esportivas abertas;
- alguns convites e participantes para mostrar estados reais.

## Primeiro Plano De Implementacao

1. Introduzir entidades base: esporte, perfil esportivo, esportes do perfil e disponibilidade.
2. Criar discovery simples por esporte + distancia aproximada.
3. Criar sessoes esportivas e participacao.
4. Criar perfil de professor e aulas.
5. Adicionar conexoes/convites.
6. Adicionar denuncias/bloqueios.
7. Evoluir IA de clustering antigo para matching/recommendation auditavel.
