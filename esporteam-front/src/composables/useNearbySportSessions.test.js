import assert from 'node:assert/strict'
import { createNearbySportSessionView } from '../features/participant/nearbySession.js'
import { useNearbySportSessions } from './useNearbySportSessions.js'

const participationUpdates = []
const nearby = useNearbySportSessions({
  listSessions: async (params, options) => {
    assert.equal(params.distanceKm, 10)
    assert.equal(options.useMockFallback, false)
    return [
      {
        id: 'nearby-card-1',
        distanceKm: 2.4,
        session: {
        id: 'nearby-session-1',
        title: 'Tenis no clube',
        modality: { name: 'Tenis' },
        entryMode: 'publica_direta',
        participantCount: 8,
      },
      },
    ]
  },
  joinSession: async (sessionId) => {
    assert.equal(sessionId, 'nearby-session-1')
    return {
      id: 'nearby-session-1',
      participantCount: 9,
      participationState: {
        status: 'confirmed',
        label: 'Confirmado',
        backendStatus: 'joined',
      },
    }
  },
  onParticipationUpdated: (detail) => participationUpdates.push(detail),
})

await nearby.loadNearbySportSessions({ id: 'sport-profile-1' }, { distanceKm: 10 })
assert.equal(nearby.nearbySessionsLoading.value, false)
assert.equal(nearby.nearbySessionsError.value, null)
assert.equal(nearby.nearbySessionCards.value.length, 1)
assert.equal(createNearbySportSessionView(nearby.nearbySessionCards.value[0]).modalityIcon, 'sportTennis')

assert.equal(await nearby.submitNearbySessionParticipation(nearby.nearbySessionCards.value[0]), true)
assert.equal(nearby.nearbySessionParticipationFeedback.value, 'Confirmado')
assert.equal(nearby.nearbySessionParticipationFeedbackTone.value, 'success')
assert.equal(nearby.nearbySessionCards.value[0].session.participationStatus, 'joined')
assert.equal(nearby.nearbySessionCards.value[0].session.participantCount, 9)
assert.equal(participationUpdates[0].id, 'nearby-session-1')

const rejected = useNearbySportSessions({
  listSessions: async () => [],
  joinSession: async () => {
    const err = new Error('duplicate')
    err.response = { data: { message: 'Perfil Esportivo ja confirmou interesse nesta Sessao Esportiva.' } }
    throw err
  },
})

assert.equal(await rejected.submitNearbySessionParticipation({ session: { id: 'nearby-session-2' } }), false)
assert.equal(rejected.nearbySessionParticipationFeedback.value, 'Perfil Esportivo ja confirmou interesse nesta Sessao Esportiva.')
assert.equal(rejected.nearbySessionParticipationFeedbackTone.value, 'error')
