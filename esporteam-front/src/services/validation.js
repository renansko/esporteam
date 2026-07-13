const VALIDATION_MESSAGES = {
  'validation.required': 'Este campo é obrigatório.',
  'validation.email': 'Informe um e-mail válido.',
  'validation.string': 'Informe um texto válido.',
  'validation.min.string': 'O valor informado é muito curto.',
  'validation.max.string': 'O valor informado é muito longo.',
  'validation.confirmed': 'A confirmação não confere.',
  'validation.exists': 'O valor selecionado não está disponível.',
}

const VALIDATION_FIELD_LABELS = {
  sport_slug: 'Modalidade',
  sport_id: 'Modalidade',
}

const API_MESSAGES = {
  'The given data was invalid.': 'Os dados informados são inválidos.',
  'The given data was invalid': 'Os dados informados são inválidos.',
  'Network Error': 'Não foi possível conectar ao servidor.',
  login_failed: 'Não foi possível entrar.',
  register_failed: 'Não foi possível criar o acesso.',
  workspace_list_failed: 'Não foi possível carregar os Workspaces.',
  workspace_create_failed: 'Não foi possível criar o Workspace.',
  workspace_select_failed: 'Não foi possível selecionar o Workspace.',
  load_failed: 'Não foi possível carregar os dados.',
  create_failed: 'Não foi possível criar a ideia.',
  login_no_token: 'Não foi possível concluir o acesso.',
  register_no_token: 'Não foi possível concluir o cadastro.',
}

export function normalizeValidationErrors(errorOrPayload) {
  const payload = errorOrPayload?.response?.data || errorOrPayload || {}
  const errors = payload.errors
  if (!errors || typeof errors !== 'object' || Array.isArray(errors)) return {}

  return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [
    field,
    (Array.isArray(messages) ? messages : [messages])
      .filter(Boolean)
      .map((message) => formatValidationMessage(message, field)),
  ]))
}

export function formatValidationMessage(message, field = '') {
  if (typeof message !== 'string') return String(message || '')
  if (VALIDATION_MESSAGES[message]) return VALIDATION_MESSAGES[message]

  const translated = message.match(/^validation\.([a-z_]+)$/)?.[1]
  if (translated === 'required') return 'Este campo é obrigatório.'
  if (translated === 'email') return 'Informe um e-mail válido.'
  if (translated === 'confirmed') return 'A confirmação não confere.'

  // Some endpoints return the raw attribute name as the message.
  return message.replaceAll('_', ' ')
}

export function translateApiMessage(message, fallback = 'Não foi possível concluir a operação.') {
  if (typeof message !== 'string' || !message.trim()) return fallback
  return API_MESSAGES[message] || message
}

export function apiErrorMessage(errorOrPayload, fallback = 'Não foi possível concluir a operação.') {
  const payload = errorOrPayload?.response?.data || errorOrPayload || {}
  const [field, messages] = Object.entries(payload.errors || {})[0] || []
  const message = Array.isArray(messages) ? messages[0] : messages

  if (message === 'validation.exists' && VALIDATION_FIELD_LABELS[field]) {
    return `A ${VALIDATION_FIELD_LABELS[field]} selecionada não está disponível.`
  }

  if (message) return formatValidationMessage(message, field)
  return translateApiMessage(payload.message || errorOrPayload?.message, fallback)
}

export function firstValidationError(errors, field) {
  const value = errors?.[field]
  return Array.isArray(value) ? value[0] || null : value || null
}

export function isValidField(value, { type = 'text', required = false } = {}) {
  const normalized = String(value ?? '').trim()
  if (required && !normalized) return false
  if (!normalized) return !required
  if (type === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)
  return true
}
