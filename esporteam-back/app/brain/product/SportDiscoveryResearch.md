# Pesquisa: referencias para descoberta esportiva e match

Data: 2026-07-05

Objetivo: extrair padroes de produto e UX de Tinder, MeetPlay e DeuJogo para orientar uma experiencia de descoberta esportiva no Esporteam, com foco em match por interesse mutuo, filtros de esporte/localizacao, descoberta de quadras/jogadores/sessoes, disponibilidade, seguranca, estados vazios, sinais de confianca e conversao de navegacao para convite/match.

Nota de produto Esporteam: o match esportivo nao precisa ser apenas 1:1. Uma sessao pode ter um anfitriao que seleciona perfis compativeis para convidar ou aprovar, formando um match em grupo. O anfitriao tambem pode criar uma sessao publica, em que perfis entram ou pedem vaga sem existir match previo entre anfitriao e participante.

## Fontes consultadas

- Tinder: pagina oficial "Match. Chat. Meet.", Central de Ajuda em portugues sobre descoberta, mensagens, algoritmo de matches, e pagina oficial de dicas de seguranca.
- MeetPlay: pagina oficial inicial, que tambem contem FAQ e proposta de valor para esportistas e gestores. A rota solicitada `https://www.meetplay.com.br/como-usar` aparece como "Como Funciona" no menu, mas o fetch da rota especifica falhou; usei a pagina oficial inicial, que referencia essa secao e expoe o mesmo conjunto de funcionalidades.
- DeuJogo: paginas oficiais `https://deujogo.com/esportes`, `https://deujogo.com/quadras`, `https://deujogo.com/institucional/como-funciona`, `https://deujogo.com/institucional/para-jogadores`, `https://deujogo.com/central-ajuda` e uma pagina de estabelecimento (`https://deujogo.com/e/arena-hj`).

## Tinder: padroes de match e descoberta

- O Tinder posiciona a experiencia em tres atos simples: descobrir perfis, dar like e conversar com matches. A pagina oficial resume a promessa como "Match. Chat. Meet." e destaca o gesto de Swipe Right como o inicio da conexao. Fonte: [Tinder - Match. Chat. Meet.](https://tinder.com/feature/swipe).
- A Descoberta e o espaco onde o usuario ve perfis. As preferencias controlam quem aparece, com ajustes como idade, distancia e orientacao sexual; distancia e um filtro explicito no produto. Fonte: [Tinder - Configuracoes de descoberta](https://www.help.tinder.com/hc/pt-br/articles/115003340963-Configura%C3%A7%C3%B5es-de-descoberta).
- O produto permite desligar a Descoberta; quando desligada, o perfil deixa de aparecer para novas pessoas, mas conversas e matches existentes continuam. Isso separa privacidade/visibilidade de relacionamento ja iniciado. Fonte: [Tinder - Configuracoes de descoberta](https://www.help.tinder.com/hc/pt-br/articles/115003340963-Configura%C3%A7%C3%B5es-de-descoberta).
- A conversa so fica disponivel depois de interesse mutuo. A Central de Ajuda diz que apenas usuarios que demonstraram interesse um pelo outro podem conversar, e em outro artigo reforca que a conversa comeca apos o match. Fontes: [Tinder - Como enviar mensagem para alguem](https://www.help.tinder.com/hc/pt-br/articles/115003341183-Como-enviar-mensagem-para-algu%C3%A9m) e [Tinder - Enviar mensagem para um match](https://www.help.tinder.com/hc/pt-br/articles/115003341583-Enviar-mensagem-para-um-match).
- O ranking de matches usa atividade recente como sinal importante, para evitar mostrar perfis inativos. A explicacao oficial tambem cita localizacao, idade, distancia, preferencias de genero, interesses/estilo de vida, sinais de fotos e historico de curtidas/deslizadas. Fonte: [Tinder - Metodo por tras dos Matches](https://www.help.tinder.com/hc/pt-br/articles/7606685697037-Aprimorando-o-Tinder-O-m%C3%A9todo-por-tr%C3%A1s-dos-Matches).
- Proximidade e apresentada como fator relevante porque a experiencia deve facilitar encontro real, nao apenas descoberta abstrata. Fonte: [Tinder - Metodo por tras dos Matches](https://www.help.tinder.com/hc/pt-br/articles/7606685697037-Aprimorando-o-Tinder-O-m%C3%A9todo-por-tr%C3%A1s-dos-Matches).
- O Tinder recomenda manter conversas na plataforma enquanto se conhece alguem, reportar comportamento suspeito/ofensivo, encontrar em local publico, compartilhar planos com amigos/familia e controlar transporte. Esses padroes sao relevantes para seguranca em encontros presenciais. Fonte: [Tinder - Safety tips](https://policies.tinder.com/community-resources/safety-tips/intl/en/).

### Padroes aproveitaveis

- Match como "double opt-in": so abre chat/convite direto quando ambos querem jogar/conversar.
- No Esporteam, esse padrao deve ser adaptado para contexto esportivo: o double opt-in pode acontecer entre dois perfis ou entre um anfitriao e varios participantes de uma sessao.
- Ranking simples e explicavel: proximidade, atividade recente, interesses em comum e comportamento anterior.
- Controle de visibilidade: participar da descoberta deve ser reversivel sem apagar conexoes existentes.
- Conversao pos-match: proxima acao clara depois do match, como "combinar horario", "entrar em grupo" ou "criar jogo".
- Seguranca por design: denunciar, bloquear, manter conversa no app e preferir locais publicos/verificados.

## MeetPlay: rede social esportiva, espacos e eventos

- O MeetPlay se posiciona como uma rede para conectar pessoas pelo esporte, com a promessa de descobrir pessoas proximas que querem jogar ou treinar, quadras e espacos disponiveis, parceiros de treino, grupos, eventos e agenda esportiva. Fonte: [MeetPlay - pagina inicial](https://www.meetplay.com.br/).
- A pagina lista uma experiencia integrada: rede social para esportistas, montar equipe, gerenciar agenda, organizar um "Meet", criar grupo e publicar espaco. Fonte: [MeetPlay - pagina inicial](https://www.meetplay.com.br/).
- A funcionalidade "Organize um Meet" e descrita como criacao de eventos esportivos com local, data e hora para convidar pessoas e receber confirmacoes. Fonte: [MeetPlay - pagina inicial](https://www.meetplay.com.br/).
- Para gestores, a pagina destaca publicar espacos esportivos para reserva, controlar informacoes do local, ter pagina propria, gerenciar horarios disponiveis, confirmar reservas e evitar conflitos de agenda. Fonte: [MeetPlay - pagina inicial](https://www.meetplay.com.br/).
- A pagina de FAQ descreve a MeetPlay como plataforma de gestao de espacos esportivos com cadastro de espacos e reserva rapida; tambem diz que esportistas podem usar a plataforma para agendamentos e interacao com amigos, enquanto planos pagos sao para gestores. Fonte: [MeetPlay - FAQ na pagina inicial](https://www.meetplay.com.br/).

### Padroes aproveitaveis

- Descoberta deve combinar pessoas, espacos e eventos em uma mesma jornada, nao em silos.
- Um evento esportivo precisa ter tripla minima: modalidade, local e horario.
- Confirmacao de presenca e agenda sao parte do match: o objetivo nao e so curtir perfil, e fechar uma pratica.
- Grupos/equipes reduzem repeticao: depois de um bom match, o produto deve facilitar recorrencia.
- Para espacos esportivos, disponibilidade e conflito de agenda sao informacoes de primeira ordem.

## DeuJogo: quadras, esportes e conversao para reserva

- A pagina de esportes apresenta uma grade de modalidades, cada uma com CTA "Ver Quadras", conectando escolha de esporte a locais onde se pode jogar. Fonte: [DeuJogo - Esportes](https://deujogo.com/esportes).
- A pagina de quadras mostra lista de locais com quantidade de resultados, busca geral, filtros, endereco, nota/avaliacoes, faixa de preco, modalidades atendidas e CTA "Ver Detalhes". Fonte: [DeuJogo - Quadras](https://deujogo.com/quadras).
- Os filtros de quadra incluem nome, localizacao, uso de localizacao, cidade, raio de busca, faixa de preco, ordenacao por nome/distancia/avaliacao/preco e comodidades como WiFi, vestiario, estacionamento, ducha e bar/lanchonete. Fonte: [DeuJogo - Quadras](https://deujogo.com/quadras).
- A pagina "Para Jogadores" resume a conversao: criar conta gratuita, buscar quadras na regiao, escolher horario e finalizar pagamento online, com confirmacao instantanea. Fonte: [DeuJogo - Para Jogadores](https://deujogo.com/institucional/para-jogadores).
- O DeuJogo usa sinais de confianca visiveis: estrelas e volume de avaliacoes na pagina para jogadores, avaliacoes por arena, endereco, preco, modalidades e comodidades na listagem. Fontes: [DeuJogo - Para Jogadores](https://deujogo.com/institucional/para-jogadores) e [DeuJogo - Quadras](https://deujogo.com/quadras).
- O FAQ para jogadores reforca pagamentos por PIX/cartao, confirmacao imediata apos aprovacao, cancelamento conforme politica do estabelecimento, notificacoes por WhatsApp/email, avaliacoes apos reserva e gratuidade para jogadores. Fonte: [DeuJogo - Central de Ajuda](https://deujogo.com/central-ajuda).
- A pagina de estabelecimento mostra abas de visao geral, reserva, quadras, avaliacoes e galeria; tambem exibe modalidades, comodidades, politica de cancelamento, quantidade de jogadores e quantidade de quadras. Fonte: [DeuJogo - Arena HJ](https://deujogo.com/e/arena-hj).
- A pagina "Como Funciona" descreve reservas 24/7 por app ou site, horarios disponiveis em tempo real, reserva instantanea, confirmacao automatica ou manual, agenda visual e notificacoes por WhatsApp/email/push. Fonte: [DeuJogo - Como Funciona](https://deujogo.com/institucional/como-funciona).

### Padroes aproveitaveis

- Comecar por esporte e levar para quadras e sessoes proximas e uma conversao natural: esporte -> locais -> horarios -> convite/reserva.
- Listagens precisam carregar informacao suficiente para decisao rapida: distancia/endereco, preco, avaliacao, modalidades, comodidades e disponibilidade.
- O filtro de raio deve ser explicito e facil de ajustar, com ordenacao por distancia, avaliacao e preco.
- Pagamento/reserva podem vir depois, mas a descoberta ja deve preparar o terreno com politica de cancelamento, confirmacao e regras do local.
- A pagina de local funciona como objeto confiavel: abas, fotos, avaliacoes, redes sociais, modalidades, comodidades e contadores de jogadores/quadras.

## Comparacao de padroes

| Tema | Tinder | MeetPlay | DeuJogo | Aplicacao para Esporteam |
| --- | --- | --- | --- | --- |
| Unidade inicial | Perfil de pessoa | Pessoas, espacos, grupos e eventos | Esporte e quadra | Feed misto com cards de pessoas, sessoes e locais |
| Conversao | Like mutuo -> chat | Organizar meet/convidar/confirmar | Buscar quadra -> escolher horario -> pagar | Interesse -> convite -> sessao com horario/local |
| Ranking | Atividade, proximidade, preferencias, comportamento | Proximidade, agenda, grupos, espacos | Localizacao, preco, avaliacao, esporte | Score deterministico: esporte, distancia, nivel, disponibilidade e atividade |
| Confianca | Match antes do chat, denuncia, dicas de seguranca | Comunidade, agenda, espacos publicados | Avaliacoes, endereco, regras, pagamento, notificacoes | Perfil verificado, local publico, historico, avaliacao e regras claras |
| Disponibilidade | Inferida por atividade e distancia | Agenda e confirmacoes | Horarios em tempo real | Janela de disponibilidade visivel e convite com horario proposto |

## Implicacoes para Esporteam

1. Modelar o match como intencao mutua, mas adaptar o resultado: em vez de apenas abrir chat, criar uma proxima acao esportiva. Exemplo: "Voces querem jogar beach tennis esta semana. Propor horario".
2. Permitir match em grupo: uma sessao pode ter um anfitriao que escolhe perfis compativeis para convidar ou aprovar, usando modalidade, nivel, disponibilidade, objetivo e distancia como criterios.
3. Permitir sessoes publicas sem match previo: quando o anfitriao escolher visibilidade publica, qualquer perfil elegivel pode entrar ou pedir vaga conforme a regra da sessao.
4. Separar tres modos de descoberta na UI: `Pessoas`, `Sessoes` e `Locais`. O usuario pode entrar por qualquer um, mas os cards devem convergir para o mesmo funil de convite.
5. Tornar os filtros essenciais e explicitos: modalidade, distancia/raio, nivel, objetivo (jogar, treinar, aprender, competir), disponibilidade e tipo de local.
6. Usar score explicavel no MVP: esporte em comum, proximidade, nivel compativel, disponibilidade sobreposta, intencao compativel, atividade recente e completude do perfil.
7. Tratar disponibilidade como dado central, nao detalhe de perfil. O card deve mostrar "livre hoje 19h-21h", "sabado de manha" ou "sem janela em comum".
8. Converter browse para acao com CTAs diferentes por objeto:
   - Pessoa: `Tenho interesse` ou `Convidar para jogar`.
   - Sessao: `Pedir vaga` ou `Confirmar presenca`.
   - Local: `Ver horarios` ou `Criar jogo aqui`.
9. Criar estados vazios produtivos: quando nao houver perfis no raio, sugerir ampliar distancia, remover filtro de nivel, criar uma sessao aberta ou seguir uma quadra/esporte para alerta.
10. Exibir sinais de confianca antes do convite: foto, nome, esportes, nivel, disponibilidade, bairro aproximado, amigos/grupos em comum quando houver, avaliacoes/referencias de sessoes e selo de perfil completo/verificado.
11. Para locais, copiar o padrao forte do DeuJogo: endereco/bairro, modalidades, preco/faixa, comodidades, avaliacoes, politica de cancelamento, fotos e horarios.
12. Para seguranca, manter conversa e combinacao dentro do app ate a sessao ser confirmada, oferecer bloquear/denunciar em todos os cards e mensagens, esconder coordenada precisa de usuarios e priorizar encontros em locais publicos ou estabelecimentos cadastrados.
13. Nao depender de IA para a primeira versao. IA pode explicar recomendacoes depois, mas o produto deve funcionar com filtros e score deterministico.
14. Criar recorrencia depois do primeiro encontro: transformar match bem-sucedido em grupo, dupla frequente, equipe ou sessao semanal.

## Hipoteses de UX para testar

- Um card de pessoa com `modalidade + nivel + janela livre + distancia aproximada` deve converter melhor que um card focado em bio longa.
- Um match esportivo tem mais chance de virar acao quando o convite ja inclui uma sugestao de horario e local.
- Sessoes com anfitriao devem converter melhor quando o anfitriao recebe uma lista curta de perfis compativeis para convidar, em vez de precisar buscar manualmente.
- Sessoes publicas reduzem atrito para iniciantes, porque permitem entrar em uma atividade aberta sem depender de match previo com o anfitriao.
- Mostrar quadras onde ha jogadores/sessoes ativas reduz o estado vazio de "nao conheco ninguem para jogar".
- Confirmacoes e lembretes por notificacao aumentam comparecimento, mas devem vir depois do opt-in explicito do usuario.
- O usuario iniciante precisa de "entrar em sessao aberta" mais do que "dar match com uma pessoa"; o usuario recorrente pode preferir grupos/equipes.

## Decisoes recomendadas para o MVP

- Construir primeiro o feed de descoberta com cards de pessoas e sessoes; locais entram como contexto de convite e podem virar aba propria quando houver inventario suficiente.
- Implementar `Connection` como intencao mutua entre perfis e `SportSession` como destino operacional do match.
- Em `SportSession`, diferenciar sessoes por modo de entrada: privada/por convite, publica com entrada direta, ou publica com aprovacao do anfitriao.
- Criar um fluxo de anfitriao para selecionar perfis compativeis e formar um grupo, sem limitar o produto a match 1:1.
- Criar um CTA pos-match obrigatorio: propor horario, escolher local ou abrir chat com sugestoes prontas.
- Fazer filtros persistentes por usuario, mas permitir relaxamento automatico controlado quando nao houver resultados.
- Registrar motivo de recomendacao no payload: `mesmo esporte`, `nivel compativel`, `2 km`, `disponivel sabado`, `grupo ativo`.
