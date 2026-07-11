import assert from 'node:assert/strict'
import { resolveSportIcon } from './sportIcons.js'

assert.equal(resolveSportIcon({ name: 'Tenis' }), 'sportTennis')
assert.equal(resolveSportIcon({ name: 'Tênis' }), 'sportTennis')
assert.equal(resolveSportIcon({ slug: 'beach-tennis', name: 'Beach Tennis' }), 'sportTennis')
assert.equal(resolveSportIcon({ name: 'Volei' }), 'sportVolleyball')
assert.equal(resolveSportIcon({ name: 'Vôlei de praia' }), 'sportVolleyball')
assert.equal(resolveSportIcon({ slug: 'corrida', name: 'Corrida' }), 'sportRunning')
assert.equal(resolveSportIcon({ name: 'Modalidade nova' }), 'sportDefault')
