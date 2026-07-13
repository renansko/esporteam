import assert from 'node:assert/strict'
import { nextTick, ref } from 'vue'
import { useTeacherProfileEditor } from './useTeacherProfileEditor.js'

const teacherProfile = ref({
  headline: 'Treinadora de corrida',
  credentials: 'CREF ativo',
  hourly_price_cents: 12000,
  service_radius_km: 15,
})
const { draft, hourlyPrice } = useTeacherProfileEditor(teacherProfile)

assert.equal(draft.headline, 'Treinadora de corrida')
assert.equal(hourlyPrice.value, 120)

hourlyPrice.value = '135,50'
assert.equal(draft.hourly_price_cents, 13550)

teacherProfile.value = { headline: 'Professora de tênis', hourly_price_cents: 9000 }
await nextTick()
assert.equal(draft.headline, 'Professora de tênis')
assert.equal(draft.credentials, '')
assert.equal(hourlyPrice.value, 90)

console.log('teacher profile editor contracts: ok')
