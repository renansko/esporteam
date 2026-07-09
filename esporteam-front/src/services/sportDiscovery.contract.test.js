import assert from 'node:assert/strict'
import {
  listCompatibleSportSessions,
  normalizeDiscoveryCard,
  normalizeDiscoveryCards,
  normalizeDiscoverySessionFilters,
  normalizeSportProfile,
} from './sportDiscovery.js'
import { esporteamApi } from './api.js'

const profile = normalizeSportProfile({
  id: 'sport-profile-1',
  display_name: 'Ana Entusiasta',
  city: 'Florianopolis',
  region: 'SC',
  sports: [
    {
      sport: { id: 'mod-1', name: 'Corrida' },
      level: 'Iniciante',
      goals: ['Praticar com regularidade'],
    },
  ],
  availability: [
    { weekday: 'sabado', starts_at: '08:00', ends_at: '10:00' },
  ],
})

assert.equal(profile.displayName, 'Ana Entusiasta')
assert.equal(profile.role, 'Entusiasta')
assert.equal(profile.locationLabel, 'Florianopolis, SC')
assert.equal(profile.primaryModality, 'Corrida')
assert.deepEqual(profile.modalities[0], {
  id: 'mod-1',
  name: 'Corrida',
  level: 'Iniciante',
  goal: 'Praticar com regularidade',
})
assert.deepEqual(profile.availability, ['sabado 08:00-10:00'])

const card = normalizeDiscoveryCard({
  id: 'card-1',
  sport_profile_id: 'sport-profile-1',
  distance_meters: 2600,
  next_action: 'request_participation',
  sport_session: {
    id: 'session-1',
    title: 'Volei de praia tecnico',
    modality: { id: 'mod-2', name: 'Volei de praia' },
    host_sport_profile: {
      id: 'host-1',
      display_name: 'Luiz Professor',
      role: 'Professor',
    },
    starts_at: '2026-07-13T19:00:00-03:00',
    location: { label: 'Beira-mar Norte', city: 'Florianopolis', region: 'SC' },
    entry_mode: 'curated',
    participation_status: 'pending',
    participant_count: 5,
  },
})

assert.equal(card.id, 'card-1')
assert.equal(card.sportProfileId, 'sport-profile-1')
assert.equal(card.distanceLabel, '2.6 km')
assert.equal(card.entryMode, 'curated')
assert.equal(card.nextAction, 'request_participation')
assert.equal(card.participationStatus, 'pending')
assert.equal(card.session.title, 'Volei de praia tecnico')
assert.equal(card.session.hostSportProfile.displayName, 'Luiz Professor')
assert.equal(card.session.modality.name, 'Volei de praia')
assert.equal(card.session.location.label, 'Beira-mar Norte')

const backendCard = normalizeDiscoveryCard({
  id: 'backend-card-1',
  type: 'session',
  score: 0.92,
  reasons: ['availability_fit', 'level_fit'],
  distance_km: 3.4,
  recommendation_reason: 'Compativel com sua Disponibilidade',
  entry_rule: 'approval_required',
  participant_count: 6,
  vacancy_status: 'hidden',
  safety_actions: ['report'],
  host: {
    id: 'host-2',
    display_name: 'Marina Organizadora',
    role: 'Organizador',
  },
  session: {
    id: 'session-2',
    title: 'Corrida no parque',
    starts_at: '2026-07-14T07:30:00-03:00',
    location_label_public: 'Parque de Coqueiros',
    city: 'Florianopolis',
    region: 'SC',
    requires_approval: true,
    entry_mode: 'publica_aprovacao',
    min_level: 'Iniciante',
    max_level: 'Intermediario',
    participant_count: 6,
    sport: { id: 'sport-1', name: 'Corrida' },
    approved_participants: [
      { id: 'profile-2', display_name: 'Joao' },
    ],
  },
})

assert.equal(backendCard.type, 'session')
assert.equal(backendCard.score, 0.92)
assert.deepEqual(backendCard.reasons, ['availability_fit', 'level_fit'])
assert.equal(backendCard.distanceKm, 3.4)
assert.equal(backendCard.distanceLabel, '3.4 km')
assert.equal(backendCard.recommendationReason, 'Compativel com sua Disponibilidade')
assert.equal(backendCard.entryRule, 'approval_required')
assert.equal(backendCard.participantCount, 6)
assert.equal(backendCard.vacancyStatus, 'hidden')
assert.deepEqual(backendCard.safetyActions, ['report'])
assert.equal(backendCard.host.displayName, 'Marina Organizadora')
assert.equal(backendCard.session.entryMode, 'publica_aprovacao')
assert.equal(backendCard.session.requiresApproval, true)
assert.equal(backendCard.session.modality.name, 'Corrida')
assert.equal(backendCard.session.location.label, 'Parque de Coqueiros')
assert.equal(backendCard.session.level, 'Iniciante a Intermediario')
assert.equal(backendCard.session.approvedParticipants[0].displayName, 'Joao')
assert.equal(backendCard.session.raw.capacity, undefined)
assert.equal(backendCard.raw.capacity, undefined)
assert.equal(backendCard.raw.vacancy_status, undefined)
assert.equal(backendCard.raw.session.capacity, undefined)
assert.equal(backendCard.raw.session.remaining_slots, undefined)

const publicCapacityCard = normalizeDiscoveryCard({
  vacancy_status: 'available',
  session: {
    capacity: 12,
    remaining_slots: 2,
    sport: 'Corrida',
  },
})

assert.equal(publicCapacityCard.vacancyStatus, null)
assert.equal(publicCapacityCard.raw.vacancy_status, undefined)
assert.equal(publicCapacityCard.raw.session.capacity, undefined)
assert.equal(publicCapacityCard.raw.session.remaining_slots, undefined)

assert.equal(normalizeDiscoveryCards({ data: [card.raw] }).length, 1)

assert.deepEqual(normalizeDiscoverySessionFilters({
  sportSlug: 'corrida',
  level: 'iniciante',
  goal: 'treino',
  distanceKm: 20,
  weekday: 'sabado',
  startsAt: '08:00',
  endsAt: '10:00',
  participationType: 'curated',
  sport_profile_id: 'must-not-leak',
}), {
  sport_slug: 'corrida',
  level: 'iniciante',
  goal: 'treino',
  distance_km: 20,
  weekday: 'sabado',
  starts_at: '08:00',
  ends_at: '10:00',
})

const originalGet = esporteamApi.get
let requested = null
esporteamApi.get = async (url, config) => {
  requested = { url, params: config.params }
  return {
    data: {
      data: [
        {
          id: 'open-card',
          entry_rule: 'match_required',
          session: {
            id: 'open-session',
            title: 'Futebol aberto',
            entry_mode: 'publica_direta',
            sport: 'Futebol',
          },
        },
        {
          id: 'invite-card',
          entry_rule: 'match_required',
          session: {
            id: 'invite-session',
            title: 'Sessao por convite',
            entry_mode: 'convite',
            sport: 'Futebol',
          },
        },
        {
          id: 'curated-card',
          entry_rule: 'approval_required',
          session: {
            id: 'curated-session',
            title: 'Volei com curadoria',
            entry_mode: 'publica_aprovacao',
            sport: 'Volei',
          },
        },
      ],
    },
  }
}

try {
  const filteredCards = await listCompatibleSportSessions({
    sportSlug: 'volei',
    distanceKm: 10,
    participationType: 'curated',
  }, { useMockFallback: false })

  assert.deepEqual(requested, {
    url: '/discovery',
    params: {
      sport_slug: 'volei',
      distance_km: 10,
      mode: 'sessions',
    },
  })
  assert.equal(filteredCards.length, 1)
  assert.equal(filteredCards[0].id, 'curated-card')

  const openCards = await listCompatibleSportSessions({
    participationType: 'open',
  }, { useMockFallback: false })

  assert.equal(openCards.length, 1)
  assert.equal(openCards[0].id, 'open-card')
} finally {
  esporteamApi.get = originalGet
}
