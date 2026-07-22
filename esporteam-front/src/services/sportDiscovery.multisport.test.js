import assert from 'node:assert/strict'
import {
  listCompatibleSportSessions,
  mergeDiscoveryRankingRounds,
  normalizeDiscoverySessionFilters,
} from './sportDiscovery.js'

const card = id => ({ id, session: { id } })
assert.deepEqual(mergeDiscoveryRankingRounds([
  [card(1), card(3)],
  [card(2), card(1), card(4)],
]).map(item => item.id), [1, 2, 3, 4])

assert.equal(normalizeDiscoverySessionFilters({ sportSlug: 'volei' }).sport_slug, 'volei')
assert.equal(normalizeDiscoverySessionFilters({ sportSlugs: ['corrida', 'volei'] }).sport_slug, 'corrida')

let partial = null
const partialCards = await listCompatibleSportSessions({ sportSlugs: ['corrida', 'volei', 'futebol'] }, {
  useMockFallback: false,
  requestSessions: async ({ sportSlug }) => {
    if (sportSlug === 'volei') throw new Error('temporary')
    return sportSlug === 'corrida' ? [card(1), card(3)] : [card(2), card(3)]
  },
  onPartialFailure: limitation => { partial = limitation },
})
assert.deepEqual(partialCards.map(item => item.id), [1, 2, 3])
assert.deepEqual(partial, { failed: 1, total: 3 })

await assert.rejects(() => listCompatibleSportSessions({ sportSlugs: ['corrida', 'volei'] }, {
  useMockFallback: false,
  requestSessions: async () => { throw new Error('total') },
}), /total/)

console.log('multisport discovery contracts: ok')
