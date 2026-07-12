const VALIDATION_MESSAGES = {
  'validation.required': 'Este campo é obrigatório.',
  'validation.email': 'Informe um e-mail válido.',
  'validation.string': 'Informe um texto válido.',
  'validation.min.string': 'O valor informado é muito curto.',
  'validation.max.string': 'O valor informado é muito longo.',
  'validation.confirmed': 'A confirmação não confere.',
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
