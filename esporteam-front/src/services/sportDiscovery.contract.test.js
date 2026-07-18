import assert from 'node:assert/strict'
import {
  fetchSportSessionDetail,
  joinSportSession,
  joinOpenSportSession,
  listCompatibleSportSessions,
  listNearbySportSessions,
  normalizeDiscoveryCard,
  normalizeDiscoveryCards,
  normalizeDiscoverySessionFilters,
  normalizeSportSessionDetail,
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
  next_action: 'pedir_vaga',
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
    entry_mode: 'publica_aprovacao',
    entry_rule: 'approval_required',
    next_action: 'pedir_vaga',
    participation_status: 'interested',
    participant_count: 5,
  },
})

assert.equal(card.id, 'card-1')
assert.equal(card.sportProfileId, 'sport-profile-1')
assert.equal(card.distanceLabel, '2.6 km')
assert.equal(card.entryMode, 'publica_aprovacao')
assert.equal(card.nextAction, 'pedir_vaga')
assert.equal(card.participationStatus, 'interested')
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
    location: { latitude_approx: -27.5969, longitude_approx: -48.5494 },
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
assert.deepEqual(backendCard.safetyActions, ['report'])
assert.equal(backendCard.host.displayName, 'Marina Organizadora')
assert.equal(backendCard.session.entryMode, 'publica_aprovacao')
assert.equal(backendCard.session.location.latitude, -27.5969)
assert.equal(backendCard.session.location.longitude, -48.5494)
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

assert.equal(publicCapacityCard.raw.vacancy_status, undefined)
assert.equal(publicCapacityCard.raw.session.capacity, undefined)
assert.equal(publicCapacityCard.raw.session.remaining_slots, undefined)

assert.equal(normalizeDiscoveryCards({ data: [card.raw] }).length, 1)

const openDetail = normalizeSportSessionDetail({
  id: 'session-open-detail',
  title: 'Corrida aberta',
  description: 'Treino leve para Entusiastas.',
  entry_mode: 'publica_direta',
  next_action: 'entrar',
  sport: { id: 'sport-corrida', name: 'Corrida' },
  creator: { id: 'host-open', display_name: 'Marina Costa', role: 'Organizador' },
  starts_at: '2026-07-12T08:00:00-03:00',
  location_label_public: 'Parque de Coqueiros',
  meeting_point: 'Portao principal',
  min_level: 'Iniciante',
  max_level: 'Iniciante',
  participant_count: 8,
  capacity: 12,
  remaining_slots: 4,
  rules: ['Chegar cedo'],
  equipment: ['Tenis'],
  participants: [{ id: 'profile-1', display_name: 'Ana Silva' }],
})

assert.equal(openDetail.id, 'session-open-detail')
assert.equal(openDetail.title, 'Corrida aberta')
assert.equal(openDetail.description, 'Treino leve para Entusiastas.')
assert.equal(openDetail.entryMode, 'publica_direta')
assert.equal(openDetail.nextAction, 'entrar')
assert.equal(openDetail.hostSportProfile.displayName, 'Marina Costa')
assert.equal(openDetail.meetingPoint, 'Portao principal')
assert.deepEqual(openDetail.rules, ['Chegar cedo'])
assert.deepEqual(openDetail.equipment, ['Tenis'])
assert.equal(openDetail.participants[0].displayName, 'Ana Silva')
assert.equal(openDetail.raw.capacity, undefined)
assert.equal(openDetail.raw.remaining_slots, undefined)

const collectionParticipationDetail = normalizeSportSessionDetail({
  ...openDetail.raw,
  participation: [{ status: 'joined' }],
})

assert.equal(collectionParticipationDetail.participationState.status, 'confirmed')
assert.equal(collectionParticipationDetail.participationState.backendStatus, 'joined')

const curatedDetail = normalizeSportSessionDetail({
  id: 'session-curated-detail',
  title: 'Volei com curadoria',
  description: 'Sessao guiada pelo Professor.',
  entry_mode: 'publica_aprovacao',
  entry_rule: 'approval_required',
  next_action: 'pedir_vaga',
  requires_approval: true,
  participation_status: 'interested',
  sport: { id: 'sport-volei', name: 'Volei de praia' },
  creator: { id: 'host-curated', display_name: 'Luiz Pereira', role: 'Professor' },
  starts_at: '2026-07-13T19:00:00-03:00',
  location_label_public: 'Beira-mar Norte',
  meeting_point: 'Posto 3',
  rules: ['Chegar antes'],
  equipment: ['Agua'],
})

assert.equal(curatedDetail.entryMode, 'publica_aprovacao')
assert.equal(curatedDetail.participationState.status, 'pending')
assert.equal(curatedDetail.participationState.label, 'Aguardando aprovacao')

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
const originalPost = esporteamApi.post
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

  esporteamApi.get = async (url, config) => {
    requested = { url, params: config.params }
    return {
      data: {
        data: [
          {
            id: 'nearby-open',
            distance_km: 2.1,
            entry_mode: 'publica_direta',
            participant_count: 8,
            title: 'Corrida no parque',
            sport: 'Corrida',
          },
          {
            id: 'nearby-curated',
            distance_km: 4.4,
            entry_mode: 'publica_aprovacao',
            entry_rule: 'approval_required',
            participant_count: 5,
            title: 'Volei com curadoria',
            sport: 'Volei',
          },
        ],
      },
    }
  }

  const nearbyCards = await listNearbySportSessions({
    distanceKm: 10,
    participationType: 'curated',
  }, { useMockFallback: false })

  assert.deepEqual(requested, {
    url: '/sessions',
    params: {
      distance_km: 10,
    },
  })
  assert.equal(nearbyCards.length, 1)
  assert.equal(nearbyCards[0].id, 'nearby-curated')

  esporteamApi.get = async (url) => {
    assert.equal(url, '/sessions/session-open-detail')
    return { data: { data: openDetail.raw } }
  }

  const fetchedDetail = await fetchSportSessionDetail('session-open-detail', { useMockFallback: false })
  assert.equal(fetchedDetail.title, 'Corrida aberta')

  esporteamApi.post = async (url) => {
    assert.equal(url, '/sessions/session-open-detail/join')
    return {
      data: {
        data: {
          ...openDetail.raw,
          session_participants: [{ status: 'joined' }],
        },
      },
    }
  }

  const joinedDetail = await joinOpenSportSession('session-open-detail', { useMockFallback: false })
  assert.equal(joinedDetail.participationState.status, 'confirmed')
  assert.equal(joinedDetail.participationState.label, 'Confirmado')
  assert.equal(joinedDetail.participationState.backendStatus, 'joined')

  esporteamApi.post = async (url) => {
    assert.equal(url, '/sessions/session-curated-detail/join')
    return {
      data: {
        data: {
          ...curatedDetail.raw,
          session_participants: [{ status: 'interested' }],
        },
      },
    }
  }

  const requestedDetail = await joinSportSession('session-curated-detail')
  assert.equal(requestedDetail.participationState.status, 'pending')
  assert.equal(requestedDetail.participationState.label, 'Aguardando aprovacao')
  assert.equal(requestedDetail.participationState.backendStatus, 'interested')
} finally {
  esporteamApi.get = originalGet
  esporteamApi.post = originalPost
}
