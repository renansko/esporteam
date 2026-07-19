import assert from 'node:assert/strict'
import { useOccurrenceManagement } from './useOccurrenceManagement.js'

const management = useOccurrenceManagement({
  updateOne: async () => { throw new Error('offline') },
  updateFuture: async (id, payload) => ({ id, ...payload, title: 'Atualizada' }),
  cancel: async (id, payload) => ({ id, ...payload, status: 'cancelled' }),
})

management.begin({ id: 9, version: 2, participant_count: 3, title: 'Original' }, { occurrences: 4, participants: 12 })
assert.deepEqual(management.impact.value, { occurrences: 4, participants: 12 })
assert.equal(await management.save(), null)
assert.equal(management.draft.value.title, 'Original')
assert.ok(management.error.value)

management.scope.value = 'future'
assert.equal((await management.save()).title, 'Atualizada')
assert.equal((await management.cancelOccurrence('Chuva')).status, 'cancelled')
