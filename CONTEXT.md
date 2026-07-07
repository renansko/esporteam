# Esporteam

Esporteam helps people find other people, teachers, groups, and opportunities around sports they want to practice. This context defines the product language for sport discovery and participation.

## Language

**Perfil Esportivo**:
A public sport identity that represents a person inside sport discovery. A person may authenticate as a user elsewhere, but discovery, matching, groups, and teacher relationships happen between sport profiles.
_Avoid_: Pessoa, usuario, conta, profile

**Entusiasta**:
A sport profile looking for people, teachers, groups, or opportunities to practice a sport. An enthusiast may be beginner, experienced, or competitive; the term describes intent to participate, not skill level.
_Avoid_: Aluno, atleta, usuario comum

**Professor**:
A sport profile that offers professional guidance for one or more sports. A teacher is also a sport profile and may participate socially outside the teacher role.
_Avoid_: Instrutor, treinador, coach

**Organizador**:
A sport profile that hosts sport sessions, groups, or local activities. An organizer may have a platform subscription, but cannot charge participants through a sport session.
_Avoid_: Dono do evento, promotor, vendedor de vaga

**Sessao Esportiva**:
A one-off free sport activity hosted by a sport profile. A session belongs to global sport discovery and is not scoped to a workspace today.
_Avoid_: Evento pago, vaga, produto, turma

**Anfitriao da Sessao**:
The sport profile responsible for a sport session, including invitations and participant decisions when approval is needed.
_Avoid_: Dono do evento, tenant, administrador do workspace

**Aluno**:
A sport profile that has an explicit learning relationship with a teacher. A sport profile should not be called a student merely because it wants to learn something.
_Avoid_: Cliente, estudante, aprendiz

**Assinatura da Plataforma**:
The commercial relationship that may unlock organizer or enthusiast capabilities in Esporteam. A subscription is account/platform billing and must not be modeled as a participant fee for a sport session.
_Avoid_: Taxa da sessao, ingresso, pagamento do evento

**Modalidade**:
A sport or physical practice that people can discover, teach, learn, or organize around.
_Avoid_: Esporte, categoria esportiva, atividade

**Pratica do Perfil**:
The relationship between a sport profile and a modality, including level, goals, preferred positions, and whether it is the profile's primary practice.
_Avoid_: Esporte do usuario, modalidade do usuario, habilidade

**Nivel Esportivo**:
The self-declared ability range of a sport profile in a modality.
_Avoid_: Ranking, faixa, nota

**Objetivo Esportivo**:
The reason a sport profile wants to practice a modality, such as playing casually, training, learning, or competing.
_Avoid_: Interesse, meta generica, intencao

**Disponibilidade**:
A recurring time window when a sport profile is normally available to practice, teach, learn, or join sport activities.
_Avoid_: Agenda, horario, calendario

**Conexao**:
A directed social request or relationship between two sport profiles, including friendship and blocking. A block removes the pair from mutual discovery.
_Avoid_: Amizade, contato, relacao

**Grupo Esportivo**:
A set of sport profiles organized around a modality or recurring sport participation.
_Avoid_: Turma, time, comunidade

**Membro do Grupo**:
A sport profile participating in a sport group with a role and membership status.
_Avoid_: Participante, integrante, membro

**Descoberta**:
The experience of finding compatible sport profiles, teachers, groups, or opportunities based on modality, location, level, availability, objectives, and privacy rules.
_Avoid_: Busca, matching, recomendacao

**Match de Sessao**:
The compatibility gate that makes a sport session visible or actionable for a sport profile. It combines sport fit, approximate location, level, availability, safety, and internal capacity without exposing remaining slots.
_Avoid_: Consulta de vagas, disponibilidade publica, lotacao

**Visibilidade do Perfil**:
The discovery exposure chosen by a sport profile. Hidden profiles do not participate in public discovery.
_Avoid_: Privacidade, status publico, publicacao

**Workspace**:
A future administrative boundary outside the current sport discovery model. It must not scope public discovery, session matching, or sport participation today.
_Avoid_: Tenant atual, clube obrigatorio, organizacao do match
