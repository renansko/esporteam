import assert from 'node:assert/strict'
import { useDiscoverySessions } from './useDiscoverySessions.js'
import { esporteamApi } from '../services/api.js'

const deck = useDiscoverySessions({
  initialCards: [
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

  assert.equal(deck.discoverySessionCards.value.length, 0)
  assert.equal(deck.discoverySessionsError.value.title, 'Descoberta sem atualizacao')
  assert.match(deck.discoverySessionsError.value.description, /tente novamente/)
} finally {
  esporteamApi.get = originalGet
}
