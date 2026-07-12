import assert from 'node:assert/strict'
import { firstValidationError, isValidField, normalizeValidationErrors } from './validation.js'

const errors = normalizeValidationErrors({ errors: { windows: ['validation.required'], email: 'validation.email' } })
assert.deepEqual(errors, {
  windows: ['Este campo é obrigatório.'],
  email: ['Informe um e-mail válido.'],
})
assert.equal(firstValidationError(errors, 'windows'), 'Este campo é obrigatório.')
assert.equal(isValidField('renan@sko.com', { type: 'email', required: true }), true)
assert.equal(isValidField('renansk', { type: 'email', required: true }), false)
assert.equal(isValidField('', { required: true }), false)
console.log('validation contracts: ok')
