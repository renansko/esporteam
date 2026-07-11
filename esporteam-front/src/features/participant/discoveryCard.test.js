import assert from 'node:assert/strict'
import {
  createSportSessionCardView,
  resolveSessionEntryBadge,
} from './discoveryCard.js'

const openBadge = resolveSessionEntryBadge({
  entryMode: 'publica_direta',
  entryRule: 'match_required',
})

assert.deepEqual(openBadge, {
  kind: 'open',
  icon: 'check',
  label: 'Aberta',
  toneClass: 'session-entry-badge-open',
  description: 'Participacao confirmada ao demonstrar interesse',
})

const curatedBadge = resolveSessionEntryBadge({
  entryMode: 'publica_aprovacao',
  entryRule: 'approval_required',
})

assert.deepEqual(curatedBadge, {
  kind: 'curated',
  icon: 'lock',
  label: 'Com curadoria',
  toneClass: 'session-entry-badge-curated',
  description: 'Anfitriao da Sessao aprova a participacao',
})

const cardView = createSportSessionCardView({
  id: 'card-1',
  type: 'session',
  distanceKm: 2.4,
  distanceLabel: '2.4 km',
  recommendationReason: 'Boa compatibilidade com sua Disponibilidade',
  entryMode: 'publica_aprovacao',
  entryRule: 'approval_required',
  participantCount: 5,
  session: {
    id: 'session-1',
    title: 'Volei de praia tecnico',
    modality: { id: 'mod-volei', name: 'Volei de praia' },
    hostSportProfile: {
      id: 'host-1',
      displayName: 'Luiz Pereira',
      role: 'Professor',
    },
    startsAt: '2026-07-13T19:00:00-03:00',
    location: {
      label: 'Beira-mar Norte',
      city: 'Florianopolis',
      region: 'SC',
    },
    level: 'Iniciante',
    participantCount: 5,
    raw: {
      capacity: 12,
      remaining_capacity: 7,
    },
  },
  raw: {
    capacity: 12,
    remaining_slots: 7,
  },
})

assert.equal(cardView.title, 'Volei de praia tecnico')
assert.equal(cardView.modalityLabel, 'Volei de praia')
assert.equal(cardView.hostLabel, 'Luiz Pereira')
assert.equal(cardView.hostRoleLabel, 'Professor')
assert.equal(cardView.distanceLabel, '2.4 km')
assert.equal(cardView.dateTimeLabel, '13/07, 19:00')
assert.equal(cardView.levelLabel, 'Iniciante')
assert.equal(cardView.participantCountLabel, '5 participantes')
assert.equal(cardView.entryBadge.label, 'Com curadoria')
assert.equal(cardView.recommendationReason, 'Boa compatibilidade com sua Disponibilidade')
assert.match(cardView.accessibilityLabel, /Sessao Esportiva Volei de praia tecnico/)
assert.match(cardView.accessibilityLabel, /Com curadoria/)

const visibleText = [
  cardView.title,
  cardView.modalityLabel,
  cardView.hostLabel,
  cardView.distanceLabel,
  cardView.dateTimeLabel,
  cardView.levelLabel,
  cardView.participantCountLabel,
  cardView.entryBadge.label,
  cardView.recommendationReason,
].join(' ')

assert.doesNotMatch(visibleText, /capacity|capacidade|vaga|slot|remaining/i)
