import { computed, ref } from 'vue'
import { acceptBioSuggestion, createBioSuggestion, listBioSuggestions } from '../services/api.js'
import { apiErrorMessage } from '../services/validation.js'

function responsePayload(error) {
  return error?.response?.data || {}
}

function responseCode(error) {
  return responsePayload(error).code || null
}

function retryAfterSeconds(error) {
  const payload = responsePayload(error)
  const header = error?.response?.headers?.['retry-after']
  const value = payload.retry_after_seconds ?? header
  const seconds = Number(value)
  return Number.isFinite(seconds) && seconds > 0 ? seconds : null
}

function makeIdempotencyKey() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()
  return `bio-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

export function buildBioInstruction({ message = '' } = {}) {
  return message.trim().slice(0, 500)
}

export function bioSuggestionFailureMessage(failureCode) {
  const messages = {
    insufficient_context: 'Adicione mais detalhes ao Perfil Esportivo antes de gerar uma bio.',
    unsafe_instruction: 'Revise sua orientação e tente novamente sem dados pessoais ou conteúdo inadequado.',
    unsafe_output: 'Não foi possível usar esta sugestão com segurança. Tente novamente.',
    output_rejected: 'Não foi possível usar esta sugestão com segurança. Tente novamente.',
    provider_unavailable: 'O assistente está indisponível agora. Tente novamente mais tarde.',
    rate_limited: 'Você atingiu o limite de sugestões. Tente novamente mais tarde.',
  }
  return messages[failureCode] || 'Não foi possível gerar esta sugestão. Tente novamente.'
}

export function useBioSuggestionWizard({
  create = createBioSuggestion,
  list = listBioSuggestions,
  accept = acceptBioSuggestion,
} = {}) {
  const open = ref(false)
  const message = ref('')
  const suggestion = ref(null)
  const suggestions = ref([])
  const loading = ref(false)
  const loadingSuggestions = ref(false)
  const accepting = ref(false)
  const error = ref(null)
  const contextMissing = ref(false)
  const retryAfter = ref(null)
  const generationRequest = ref(null)
  const page = ref(1)
  const hasMoreSuggestions = ref(false)
  let retryTimer = null

  const instruction = computed(() => buildBioInstruction({ message: message.value }))

  async function show() {
    open.value = true
    error.value = null
    contextMissing.value = false
    await loadSuggestions()
  }

  function close() {
    open.value = false
  }

  async function generate() {
    if (!create || loading.value) return null
    const pending = suggestions.value.find(item => item.status === 'generated' && item.bio)
    if (pending) {
      suggestion.value = pending
      return pending
    }
    loading.value = true
    error.value = null
    contextMissing.value = false
    retryAfter.value = null
    const nextInstruction = instruction.value
    if (!generationRequest.value || generationRequest.value.instruction !== nextInstruction) {
      generationRequest.value = { instruction: nextInstruction, key: makeIdempotencyKey() }
    }
    try {
      const created = await create({ instruction: nextInstruction, idempotencyKey: generationRequest.value.key })
      suggestion.value = created
      upsertSuggestion(created)
      message.value = ''
      generationRequest.value = null
      retryAfter.value = null
      if (retryTimer) clearTimeout(retryTimer)
      return created
    } catch (err) {
      const code = responseCode(err)
      contextMissing.value = code === 'insufficient_context'
      retryAfter.value = code === 'rate_limited' ? retryAfterSeconds(err) : null
      if (retryTimer) clearTimeout(retryTimer)
      if (retryAfter.value) {
        retryTimer = setTimeout(() => {
          retryAfter.value = null
          retryTimer = null
        }, retryAfter.value * 1000)
        retryTimer?.unref?.()
      }
      error.value = retryAfter.value
        ? `Você atingiu o limite de sugestões. Tente novamente em ${retryAfter.value} segundos.`
        : bioSuggestionFailureMessage(code) === 'Não foi possível gerar esta sugestão. Tente novamente.'
          ? apiErrorMessage(err, 'Não foi possível gerar uma sugestão agora.')
          : bioSuggestionFailureMessage(code)
      return null
    } finally {
      loading.value = false
    }
  }

  function upsertSuggestion(next) {
    if (!next?.id) return
    const index = suggestions.value.findIndex(item => item.id === next.id)
    if (index === -1) suggestions.value = [next, ...suggestions.value]
    else suggestions.value.splice(index, 1, next)
  }

  async function loadSuggestions({ nextPage = 1, append = false } = {}) {
    if (!list || loadingSuggestions.value) return suggestions.value
    loadingSuggestions.value = true
    try {
      const listed = await list({ page: nextPage })
      const items = Array.isArray(listed) ? listed : listed?.items
      const validItems = Array.isArray(items) ? items.filter(item => item.status !== 'failed') : []
      suggestions.value = append ? [...suggestions.value, ...validItems] : validItems
      page.value = nextPage
      const meta = Array.isArray(listed) ? null : listed?.meta
      hasMoreSuggestions.value = Boolean(meta?.current_page && meta?.last_page && meta.current_page < meta.last_page)
      suggestion.value = suggestions.value.find(item => item.status === 'generated') || suggestions.value[0] || null
      return suggestions.value
    } catch {
      // A criação de uma nova sugestão continua disponível se o histórico falhar.
      return suggestions.value
    } finally {
      loadingSuggestions.value = false
    }
  }

  function loadNextSuggestions() {
    if (!hasMoreSuggestions.value) return suggestions.value
    return loadSuggestions({ nextPage: page.value + 1, append: true })
  }

  async function acceptSuggestion(next = suggestion.value) {
    if (!accept || accepting.value || !next?.id) return null
    accepting.value = true
    error.value = null
    try {
      const accepted = await accept(next.id)
      suggestion.value = accepted
      upsertSuggestion(accepted)
      return accepted
    } catch (err) {
      error.value = apiErrorMessage(err, 'Não foi possível usar esta bio agora.')
      return null
    } finally {
      accepting.value = false
    }
  }

  function selectSuggestion(suggestionId) {
    const selected = suggestions.value.find(item => item.id === suggestionId)
    if (!selected) return
    suggestion.value = selected
    if (selected.status === 'failed') error.value = bioSuggestionFailureMessage(selected.failure_code)
    else error.value = null
  }

  return {
    open,
    message,
    suggestion,
    suggestions,
    loading,
    loadingSuggestions,
    accepting,
    error,
    contextMissing,
    retryAfter,
    page,
    hasMoreSuggestions,
    instruction,
    show,
    close,
    generate,
    loadSuggestions,
    loadNextSuggestions,
    acceptSuggestion,
    selectSuggestion,
  }
}
