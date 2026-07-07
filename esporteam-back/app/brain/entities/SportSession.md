# SportSession

Sessao esportiva pontual criada por um Perfil Esportivo para partida, treino, corrida, aula aberta ou encontro.

## Campos

- `creator_profile_id`: Perfil Esportivo que criou a sessao.
- `sport_id`: modalidade opcional.
- `title`, `description`, `type`, `starts_at`.
- `location_label`, `city`, `region`, `latitude_approx`, `longitude_approx`.
- `capacity`: limite total de participantes ativos, incluindo o criador.
- `requires_approval`: quando verdadeiro, pedidos de vaga ficam `interested` ate aprovacao do anfitriao.
- `visibility`: `public` ou `private`.
- `status`: `open`, `cancelled` ou `completed`.

## Relacionamentos

- `creator`: belongsTo `SportProfile`.
- `sport`: belongsTo `Sport`.
- `participationRecords`: hasMany `SessionParticipant`.
- `participants`: belongsToMany `SportProfile` via `session_participants`.

## Regras

`SportSessionService` cria a sessao, adiciona o criador como participante `joined` e bloqueia entrada quando status nao e `open` ou a capacidade esta cheia.

Sessoes hospedadas usam o anfitriao (`creator_profile_id`) para listar recomendacoes, convidar perfis, receber respostas de convite e aprovar/recusar/remover interessados. Bloqueios, perfis ocultos e capacidade impedem convite ou aprovacao invalida.

Sessoes esportivas sao sempre gratuitas para participantes. Campos de cobranca como `price_cents`, `fee_cents`, `is_paid`, `payment_required` e `currency` nao pertencem a `sport_sessions` e devem ser rejeitados na criacao. Assinaturas de organizador/entusiasta sao billing da plataforma, nao taxa de evento.
