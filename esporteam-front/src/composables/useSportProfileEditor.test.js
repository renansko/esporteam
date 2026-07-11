import assert from 'node:assert/strict'
import { createSportProfileDraft } from './useSportProfileEditor.js'

const pendingProfile = createSportProfileDraft({
  displayName: 'Perfil novo',
  modalities: [{ name: 'Corrida', level: 'Iniciante', goal: 'Treino' }],
  availability: [],
})

assert.deepEqual(pendingProfile.profile, {
  display_name: 'Perfil novo',
  bio: '',
  city: '',
  region: '',
  visibility: 'public',
})
assert.deepEqual(pendingProfile.sports, [])
assert.deepEqual(pendingProfile.availability, [])

const persistedProfile = createSportProfileDraft({
  raw: {
    display_name: 'Perfil existente',
    sports: [{ sport_id: 7, sport: { id: 7, name: 'Corrida' }, level: 'beginner', goals: ['jogar'] }],
    availability: [{ weekday: 6, starts_at: '08:00', ends_at: '10:00' }],
  },
})

assert.equal(persistedProfile.sports[0].sport_id, 7)
assert.equal(persistedProfile.sports[0].level, 'beginner')
assert.deepEqual(persistedProfile.sports[0].goals, ['jogar'])
assert.deepEqual(persistedProfile.availability, [{ weekday: 6, starts_at: '08:00', ends_at: '10:00' }])
