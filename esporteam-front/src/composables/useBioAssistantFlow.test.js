import assert from 'node:assert/strict'
import { useBioAssistantFlow } from './useBioAssistantFlow.js'

const sessionStore = { getItem: () => null, setItem: () => {} }
const flow = useBioAssistantFlow({
  onboarding: { sessionStore },
  wizard: { create: async () => ({ id: 1, bio: 'Bio sugerida', status: 'generated' }) },
})

await flow.evaluate({ id: 1, bio: '', bio_assistant_onboarding: { eligible: true, blocking_fields: [] } })
assert.equal(flow.onboarding.open.value, true)
assert.equal((await flow.generate({ hasSportContext: false })).reason, 'missing_context')
assert.equal((await flow.generate({ hasSportContext: true })).suggestion.bio, 'Bio sugerida')

console.log('bio assistant flow contracts: ok')
