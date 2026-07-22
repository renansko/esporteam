import assert from 'node:assert/strict'
import { apiErrorMessage, firstValidationError, isValidField, normalizeValidationErrors } from './validation.js'

const errors = normalizeValidationErrors({ errors: { windows: ['validation.required'], email: 'validation.email' } })
assert.deepEqual(errors, {
  windows: ['Este campo é obrigatório.'],
  email: ['Informe um e-mail válido.'],
})
assert.equal(firstValidationError(errors, 'windows'), 'Este campo é obrigatório.')
assert.equal(isValidField('renan@sko.com', { type: 'email', required: true }), true)
assert.equal(isValidField('renansk', { type: 'email', required: true }), false)
assert.equal(isValidField('', { required: true }), false)
assert.equal(apiErrorMessage({ message: 'The given data was invalid.' }), 'Os dados informados são inválidos.')
assert.equal(apiErrorMessage({ message: 'Network Error' }), 'Não foi possível conectar ao servidor.')
assert.equal(
  apiErrorMessage({ code: 'adult_eligibility_required', message: 'adult_eligibility_required' }),
  'Confirme sua maioridade para continuar.',
)
assert.equal(apiErrorMessage({ message: 'Perfil Esportivo já confirmado.' }), 'Perfil Esportivo já confirmado.')
assert.equal(
  apiErrorMessage({
    message: 'The given data was invalid.',
    errors: { sport_slug: ['validation.exists'] },
  }),
  'A Modalidade selecionada não está disponível.',
)
console.log('validation contracts: ok')
