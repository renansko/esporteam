# SportSessionService
## createForUser
Assinatura: `createForUser(int $userId, array $data): SportSession`
Cria uma Sessao Esportiva para o Perfil Esportivo autenticado. Ignora qualquer autoria enviada no payload e usa o perfil resolvido por `user_id`.
Aceita `entry_mode` como `convite`, `publica_direta` ou `publica_aprovacao`. O campo legado `requires_approval = true` ainda cria sessao `publica_aprovacao` quando `entry_mode` nao foi enviado. `min_level` e `max_level` definem faixa opcional de nivel elegivel.
Side effects: grava `sport_sessions` e adiciona o criador em `session_participants` com status `joined`.
## publishOneOff
Assinatura: `publishOneOff(int $userId, array $data, string $publicationKey): SportSession`.
Publica a Sessao Esportiva pontual vinda do wizard de Mapa. Exige Perfil Esportivo com identidade e localização aproximada, horário de início/fim, fuso IANA, modalidade, entrada, área pública e ponto de encontro. A chave de publicação é idempotente por anfitrião; colisões concorrentes retornam a sessão já criada. O ponto exato é armazenado separadamente e a Descoberta recebe coordenadas arredondadas. Invalida o cache global de Descoberta.
## publishSeries
Assinatura: `publishSeries(int $userId, array $data, string $publicationKey): array`.
Cria ou retorna uma regra semanal idempotente e materializa, na mesma transacao, as ocorrencias futuras do horizonte de 90 dias. O horario e calculado no timezone IANA da serie, preservando o horario de parede em mudancas de DST.
## materializeSeries
Assinatura: `materializeSeries(SportSessionSeries $series, ?CarbonImmutable $now = null): Collection`.
Repara ou estende uma serie ativa sem duplicar ocorrencias. Cada job e somente um adaptador desta interface; com a flag desligada nao cria novas ocorrencias e as ja materializadas permanecem descobriveis.
## openSessions
Aceita bounds completos de viewport (`south`, `north`, `west`, `east`) para a superficie de Mapa. Quando presentes, limita a consulta a coordenadas aproximadas dentro do retangulo; a validacao HTTP impede viewport com mais de 10 graus em qualquer eixo.
Assinatura: `openSessions(int $userId, array $filters = []): Collection`
Lista sessoes `open` e `public`, priorizadas por distancia quando calculavel e depois por `starts_at`, limitadas a 50 itens. Filtros atuais: `sport_id`, `sport_slug`, `type`, `entry_mode`, `level`, `weekday`/`starts_at`/`ends_at`, `has_available_slots`, `city`, `region`, `starts_after`, `starts_before`. `distance_km` segue aceito por compatibilidade, mas nao exclui sessoes.
Cada sessao recebe `next_action`: `entrar`, `pedir_vaga` ou `indisponivel`, calculado por modo de entrada, bloqueio, visibilidade do perfil, faixa de nivel e capacidade. O payload publico nao expõe vagas restantes.
Side effects: nenhum.
## detailForUser
Assinatura: `detailForUser(int $userId, SportSession $session): SportSession`
Retorna detalhe de Sessao Esportiva publica e aberta. Oculta sessoes privadas/encerradas e sessoes cujo anfitriao bloqueou ou foi bloqueado pelo Perfil Esportivo autenticado. Carrega participacao somente do perfil autenticado; capacidade continua privada para nao-anfitrioes.
Side effects: nenhum.
## participantSessionsForUser
Assinatura: `participantSessionsForUser(int $userId): Collection`
Lista ate 50 Sessoes Esportivas em que o Perfil Esportivo autenticado possui participacao, ordenadas da mais recente para a mais antiga. Quando a conta ainda nao possui Perfil Esportivo, retorna uma colecao vazia. Inclui `joined`, `approved`, `interested`, `invited`, `declined`, `removed` e `left`; o front normaliza os cinco primeiros grupos para Partidas e deixa `left` fora da primeira interface, conforme PRD. Carrega somente registro de participacao do perfil autenticado para normalizacao segura no front.
Side effects: nenhum.
## recommendationsForHost
Assinatura: `recommendationsForHost(int $userId, SportSession $session, array $filters = []): Collection`
Lista perfis recomendados para o anfitriao convidar para a sessao. Filtra por modalidade da sessao, disponibilidade no horario da sessao, visibilidade publica, bloqueios, distancia, nivel e objetivo.
Side effects: nenhum.
## inviteProfiles
Assinatura: `inviteProfiles(int $userId, SportSession $session, array $profileIds): SportSession`
Permite ao anfitriao criar convites `invited` para perfis elegiveis. Rejeita perfis ocultos, bloqueados, ja participantes, capacidade insuficiente e campos de cobranca no request HTTP.
Side effects: cria ou atualiza linhas em `session_participants`.
## respondToInvite
Assinatura: `respondToInvite(int $userId, SportSession $session, string $action): SportSession`
Permite ao perfil convidado aceitar ou recusar convite. Aceite vira `approved`; recusa vira `declined`.
Side effects: atualiza status em `session_participants`.
## decideParticipant
Assinatura: `decideParticipant(int $userId, SportSession $session, SportProfile $profile, string $action): SportSession`
Permite ao anfitriao aprovar, recusar ou remover perfis do fluxo da sessao. Aprovacao respeita capacidade, bloqueios e visibilidade.
Side effects: atualiza status em `session_participants`.
## join
Assinatura: `join(int $userId, SportSession $session): SportSession`
Registra participacao do Perfil Esportivo autenticado em uma sessao existente. A sessao e travada em transacao antes de contar participantes. `publica_direta` cria `joined`; `publica_aprovacao` cria pedido `interested`; `convite` rejeita entrada publica.
Falha com validacao quando a sessao nao esta `open`, a capacidade esta cheia para entrada direta, o perfil esta bloqueado/oculto, nao respeita a faixa de nivel ou ja entrou/pediu vaga.
Side effects: cria ou reativa linha em `session_participants`.
## joinOccurrence
Assinatura: `joinOccurrence(int $userId, SportSession $occurrence): SportSession`.
Intenção explícita de participar somente da ocorrência escolhida; preserva todas as transições e proteções do fluxo de participação.
## followSeries / unfollowSeries
Cria/remove a relação única entre Perfil Esportivo e série, sem criar ou cancelar participação em ocorrência. Acompanhamento não libera ponto exato.
## changeOccurrence
Assinatura: `changeOccurrence(int $userId, SportSession $occurrence, array $data): SportSession`.
O Anfitrião altera somente uma ocorrência de série. Exige `version`, grava uma exceção duradoura e invalida a Descoberta.
## changeSeriesFromOccurrence
Assinatura: `changeSeriesFromOccurrence(int $userId, SportSession $occurrence, array $data): SportSession`.
Altera a regra a partir da ocorrência selecionada, mantendo os registros futuros equivalentes e suas participações.
## cancelOccurrence
Assinatura: `cancelOccurrence(int $userId, SportSession $occurrence, int $version, ?string $reason): SportSession`.
Cancela apenas a ocorrência; a série e os registros de participantes são preservados e `change_notice` fica disponível para Eventos.
## eventsForUser
Retorna a superfície agrupada de Eventos: próximas confirmadas, aguardando aprovação, séries acompanhadas com próximas ocorrências e atividades hospedadas.
