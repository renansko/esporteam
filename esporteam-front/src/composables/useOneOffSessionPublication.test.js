import assert from 'node:assert/strict'
import { useOneOffSessionPublication } from './useOneOffSessionPublication.js'

let publishedPayload = null
const publication = useOneOffSessionPublication({
  publish: async (payload) => {
    publishedPayload = payload
    return { id: 'session-created' }
  },
})

publication.begin({
  primaryModality: 'Corrida',
  city: 'Florianópolis',
  region: 'SC',
  raw: { sports: [{ sport_id: 7, sport: { name: 'Corrida' } }] },
})

assert.equal(publication.selectedLocation.value, null)
assert.equal(publication.canReview.value, false)
publication.selectLocation({ latitude: -27.5969, longitude: -48.5494 })
assert.deepEqual(publication.selectedLocation.value, { latitude: -27.5969, longitude: -48.5494 })
assert.equal(publication.draft.value.meeting_point_label, 'Ponto selecionado no mapa')
assert.equal(publication.canReview.value, true)

const session = await publication.publishDraft()
assert.equal(session.id, 'session-created')
assert.equal(publishedPayload.latitude, -27.5969)
assert.equal(publishedPayload.longitude, -48.5494)
assert.equal(publication.open.value, false)

console.log('one-off session publication contracts: ok')
