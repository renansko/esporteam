# SOUL - Cola Aí

> Este arquivo e a fonte curta e obrigatoria do "por que" do projeto. Todo agente deve ler isto antes de alterar produto, dominio, API, banco, UX, IA ou documentacao.

**Versao do produto:** MVP mobile de descoberta esportiva local
**Origem:** consolidado a partir de `PRD.md` do backend
**Status:** norte de produto para o pivot mobile

## Ideia Central

O Cola Aí existe para reduzir o atrito de encontrar pessoas, professores, aulas e sessoes esportivas perto de voce.

O produto nao e um sistema B2B de workspace, nem um marketplace de checkout no MVP. E um app mobile local-first para praticantes, professores e organizadores combinarem esporte com menos dependencia de WhatsApp, indicacoes soltas e busca manual.

## Problemas Que Precisamos Resolver

- Praticantes nao sabem quem joga, treina ou corre perto deles.
- Pessoas dependem de grupos informais, mensagens perdidas e convites manuais.
- Professores e tecnicos precisam divulgar aulas e encontrar alunos sem uma vitrine local confiavel.
- Organizadores precisam preencher sessoes, confirmar interesse e evitar retrabalho.
- Usuarios precisam descobrir atividades compativeis por esporte, horario, nivel, objetivo e distancia.
- Localizacao e seguranca precisam ser tratadas com cuidado: descoberta local nao pode expor endereco preciso.

## Promessa Do MVP

O MVP deve provar este loop:

1. O usuario cria um Perfil Esportivo.
2. Escolhe modalidades, nivel, objetivos e disponibilidade.
3. Descobre pessoas, aulas e sessoes proximas.
4. Envia interesse, convite ou entra em uma Sessao Esportiva.
5. Professores e organizadores recebem interessados.
6. O app melhora recomendacoes conforme uso.

## Linguagem Do Produto

Use a linguagem do dominio esportivo:

- Perfil Esportivo
- Modalidade
- Entusiasta
- Professor
- Aluno
- Anfitriao da Sessao
- Sessao Esportiva
- Descoberta
- Partidas
- Disponibilidade
- Conexao

`Workspace` e uma fronteira tecnica herdada para auth, membership e admin. Nao deixe linguagem B2B contaminar o dominio mobile.

## Regras Que Protegem A Alma Do Produto

- Descoberta e participacao acontecem entre Perfis Esportivos publicos.
- Sessoes Esportivas sao gratuitas para participantes no MVP.
- Preco pertence a perfil profissional ou aula, nunca a `sport_sessions`.
- Localizacao publica deve ser aproximada por padrao.
- Capacidade e vagas restantes nao devem aparecer em cards publicos antes de match/acao.
- Bloqueio, denuncia e visibilidade privada fazem parte do produto, nao sao detalhes secundarios.
- Chat, checkout, reserva de quadra e cobranca por participante ficam fora do primeiro MVP.
- IA deve melhorar matching, onboarding, recomendacao, explicacao e seguranca sem substituir regras deterministicas auditaveis.

## Como Agentes Devem Usar Este Arquivo

Antes de propor ou implementar mudancas, verifique se a decisao preserva esta direcao:

- A mudanca ajuda alguem a praticar esporte com menos atrito?
- A mudanca fortalece descoberta local, convite, participacao ou aulas?
- A mudanca respeita privacidade de localizacao e seguranca?
- A mudanca evita trazer complexidade de workspace, checkout ou marketplace antes da hora?
- A nomenclatura conversa com o dominio esportivo do app?

Se uma tarefa, issue ou implementacao conflitar com este arquivo, pare e registre o conflito antes de seguir.

Para detalhes de contrato, endpoints, estados e gaps tecnicos, leia `PRD.md`.
