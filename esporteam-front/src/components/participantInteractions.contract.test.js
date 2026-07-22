import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolveDiscoverySwipeAction } from '../features/participant/discoveryCard.js'
import { changeMapZoom, createLongPressGesture, focusMapSelection } from '../features/participant/mapInteraction.js'

assert.equal(resolveDiscoverySwipeAction(194, 390), null)
assert.equal(resolveDiscoverySwipeAction(195, 390), 'interest')
assert.equal(resolveDiscoverySwipeAction(-195, 390), 'skip')
assert.equal(resolveDiscoverySwipeAction(250, 390, false), null)

const mapSource = readFileSync(new URL('./NearbySessionsMap.vue', import.meta.url), 'utf8')
let scheduled = null
let confirmed = null
const gesture = createLongPressGesture({
  schedule: callback => { scheduled = callback; return 1 },
  cancelSchedule: () => { scheduled = null },
  onConfirm: value => { confirmed = value },
})
gesture.start({ x: 0, y: 0 }, 'first')
gesture.move({ x: 4, y: 4 })
scheduled()
assert.equal(confirmed, 'first')
confirmed = null
gesture.start({ x: 0, y: 0 }, 'second')
gesture.move({ x: 11, y: 0 })
assert.equal(scheduled, null)
assert.equal(confirmed, null)
const zoomCalls = []
assert.equal(changeMapZoom({ zoomIn: () => zoomCalls.push('in'), zoomOut: () => zoomCalls.push('out') }, 1), true)
changeMapZoom({ zoomIn: () => zoomCalls.push('in'), zoomOut: () => zoomCalls.push('out') }, -1)
assert.deepEqual(zoomCalls, ['in', 'out'])
let focusOptions = null
assert.equal(focusMapSelection({ focus: options => { focusOptions = options } }), true)
assert.deepEqual(focusOptions, { preventScroll: true })
assert.match(mapSource, /delay: 500/)
assert.match(mapSource, /pointerleave/)
assert.match(mapSource, /aria-label="Aumentar zoom"/)
assert.match(mapSource, /focusMapSelection\(marker\)/)

const shellSource = readFileSync(new URL('./ParticipantShell.vue', import.meta.url), 'utf8')
assert.match(shellSource, /Criar sessão/)
assert.match(shellSource, /aria-live="polite"/)
assert.match(shellSource, /role="tab"|UiSegmented/)
assert.doesNotMatch(shellSource, /\bvagas?\b/i)
assert.doesNotMatch(shellSource, /\bcapacidade\b/i)

console.log('participant interaction contracts: ok')
