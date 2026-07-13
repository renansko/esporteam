import assert from 'node:assert/strict'
import { bioSuggestionFailureMessage, buildBioInstruction, useBioSuggestionWizard } from './useBioSuggestionWizard.js'

assert.equal(
  buildBioInstruction({ message: 'Curta e direta' }),
  'Curta e direta',
)
assert.equal(buildBioInstruction({ message: `  ${'a'.repeat(501)}  ` }).length, 500)
assert.equal(bioSuggestionFailureMessage('provider_unavailable'), 'O assistente está indisponível agora. Tente novamente mais tarde.')

const calls = []
const wizard = useBioSuggestionWizard({
  create: async payload => {
    calls.push(payload)
    return { id: 2, bio: 'Nova bio', status: 'generated' }
  },
})

await wizard.show()
assert.equal(wizard.open.value, true)
wizard.message.value = 'Quero algo objetivo'
await wizard.generate()
assert.equal(calls[0].instruction, 'Quero algo objetivo')
assert.equal(wizard.suggestion.value.bio, 'Nova bio')
assert.equal(wizard.message.value, '')

const accepted = await wizard.acceptSuggestion()
assert.equal(accepted, null)

const existing = useBioSuggestionWizard({
  list: async () => [
    { id: 1, bio: 'Bio anterior', status: 'accepted' },
    { id: 2, bio: 'Bio pronta', status: 'generated' },
  ],
  accept: async id => ({ id, bio: 'Bio pronta', status: 'accepted' }),
})
await existing.show()
assert.equal(existing.suggestions.value.length, 2)
assert.equal(existing.suggestion.value.id, 2)
const acceptedSuggestion = await existing.acceptSuggestion()
assert.equal(acceptedSuggestion.status, 'accepted')
assert.equal(existing.suggestions.value.find(item => item.id === 2).status, 'accepted')
const acceptedAgain = await existing.acceptSuggestion()
assert.equal(acceptedAgain.status, 'accepted')

await existing.loadSuggestions()
existing.selectSuggestion(1)
assert.equal(existing.error.value, null)
existing.suggestions.value.push({ id: 3, bio: null, status: 'failed', failure_code: 'output_rejected' })
existing.selectSuggestion(3)
assert.equal(existing.error.value, 'Não foi possível usar esta sugestão com segurança. Tente novamente.')

const insufficient = useBioSuggestionWizard({
  create: async () => { throw { response: { status: 422, data: { code: 'insufficient_context', errors: { context: ['Preencha uma modalidade.'] } } } } },
})
await insufficient.generate()
assert.equal(insufficient.contextMissing.value, true)
assert.equal(insufficient.error.value, 'Adicione mais detalhes ao Perfil Esportivo antes de gerar uma bio.')

const limited = useBioSuggestionWizard({
  create: async () => { throw { response: { data: { code: 'rate_limited', retry_after_seconds: 18 } } } },
})
await limited.generate()
assert.equal(limited.retryAfter.value, 18)
assert.equal(limited.error.value, 'Você atingiu o limite de sugestões. Tente novamente em 18 segundos.')

const paged = useBioSuggestionWizard({
  list: async ({ page }) => ({
    items: [{ id: page, bio: `Bio ${page}`, status: 'generated' }],
    meta: { current_page: page, last_page: 2 },
  }),
})
await paged.show()
assert.equal(paged.hasMoreSuggestions.value, true)
await paged.loadNextSuggestions()
assert.equal(paged.suggestions.value.length, 2)

console.log('bio suggestion wizard contracts: ok')
