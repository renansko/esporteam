import assert from 'node:assert/strict'
import { normalizeBioAssistantOnboarding, useBioAssistantOnboarding } from './useBioAssistantOnboarding.js'

const entries = new Map()
const sessionStore = { getItem: key => entries.get(key) || null, setItem: (key, value) => entries.set(key, value) }
const profile = { id: 7, bio: '', bio_assistant_onboarding: { eligible: true, completed_at: null, blocking_fields: [] } }
const flow = useBioAssistantOnboarding({ sessionStore })

assert.equal(flow.evaluate(profile), true)
assert.equal(flow.open.value, true)
assert.equal(flow.automatic.value, true)
flow.dismissForSession()
assert.equal(flow.open.value, false)
assert.equal(flow.evaluate(profile), false)

const laterSession = useBioAssistantOnboarding({ sessionStore: { getItem: () => null, setItem: () => {} } })
assert.equal(laterSession.evaluate(profile), true)
laterSession.complete()
assert.equal(laterSession.onboarding.value.eligible, false)

assert.equal(normalizeBioAssistantOnboarding({ bio: 'Bio existente' }).eligible, false)
assert.deepEqual(
  normalizeBioAssistantOnboarding({ bio: '', bio_assistant_onboarding: { eligible: false, blocking_fields: ['sports'] } }).blockingFields,
  ['sports'],
)

console.log('bio assistant onboarding contracts: ok')
