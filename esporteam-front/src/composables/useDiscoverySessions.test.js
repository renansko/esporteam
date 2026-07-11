import assert from 'node:assert/strict'
import { useDiscoverySessions } from './useDiscoverySessions.js'
import { esporteamApi } from '../services/api.js'

const deck = useDiscoverySessions({
  initialCards: [
    null,
    {
      id: 'card-seed',
      session: {
        id: 'session-seed',
        title: 'Sessao inicial',
        sport: 'Corrida',
      },
    },
  ],
})

assert.equal(deck.discoverySessionCards.value.length, 1)
assert.equal(deck.discoverySessionCards.value[0].session.id, 'session-seed')
assert.equal(deck.hasDiscoverySessionFilters.value, false)

deck.setDiscoverySessionFilters({
  sportSlug: 'corrida',
  distanceKm: 20,
  weekday: 'sabado',
  startsAt: '08:00',
  endsAt: '10:00',
  participationType: 'open',
})

assert.equal(deck.hasDiscoverySessionFilters.value, true)
assert.equal(deck.discoverySessionFilters.sportSlug, 'corrida')
assert.equal(deck.discoverySessionFilters.distanceKm, 20)

const originalGet = esporteamApi.get
let requestedParams = null
esporteamApi.get = async (url, config) => {
  requestedParams = config.params
  return {
    data: {
      data: [
        {
          id: 'card-loaded',
          session: {
            id: 'session-loaded',
            title: 'Corrida filtrada',
            entry_mode: 'publica_direta',
            sport: 'Corrida',
          },
        },
      ],
    },
  }
}

try {
  await deck.loadCompatibleSportSessions({ id: 'sport-profile-1' })

  assert.deepEqual(requestedParams, {
    sport_slug: 'corrida',
    distance_km: 20,
    weekday: 'sabado',
    starts_at: '08:00',
    ends_at: '10:00',
    mode: 'sessions',
  })
  assert.equal(deck.discoverySessionsLoading.value, false)
  assert.equal(deck.discoverySessionsError.value, null)
  assert.equal(deck.discoverySessionCards.value.length, 1)
  assert.equal(deck.discoverySessionCards.value[0].session.title, 'Corrida filtrada')

  const err = new Error('offline')
  esporteamApi.get = async () => { throw err }
  await deck.loadCompatibleSportSessions({ id: 'sport-profile-1' })

  assert.equal(deck.discoverySessionCards.value.length, 1)
  assert.equal(deck.discoverySessionCards.value[0].session.title, 'Corrida filtrada')
  assert.equal(deck.discoverySessionsError.value.title, 'Descoberta sem atualizacao')
  assert.match(deck.discoverySessionsError.value.description, /tente novamente/)
} finally {
  esporteamApi.get = originalGet
}

const actionDeck = useDiscoverySessions({
  initialCards: [
    { id: 'action-card-1', session: { id: 'action-session-1', title: 'Corrida', entry_mode: 'publica_direta' } },
    { id: 'action-card-2', session: { id: 'action-session-2', title: 'Volei', entry_mode: 'publica_aprovacao' } },
  ],
  joinSession: async (sessionId) => ({
    id: sessionId,
    participationState: { status: 'confirmed', label: 'Confirmado', backendStatus: 'joined' },
  }),
})

assert.equal(await actionDeck.showInterestInCurrentSession(), true)
assert.equal(actionDeck.discoverySessionCards.value[0].id, 'action-card-2')
assert.match(actionDeck.discoveryActionFeedback.value, /Confirmado/)
assert.equal(actionDeck.canUndoDiscovery.value, true)
assert.equal(actionDeck.undoDiscoveryAction(), true)
assert.equal(actionDeck.discoverySessionCards.value[0].id, 'action-card-1')
assert.equal(actionDeck.skipCurrentSession(), true)
assert.equal(actionDeck.discoverySessionCards.value[0].id, 'action-card-2')
