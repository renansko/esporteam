import assert from 'node:assert/strict'
import {
  normalizeDiscoveryCard,
  normalizeDiscoveryCards,
  normalizeSportProfile,
} from './sportDiscovery.js'

const profile = normalizeSportProfile({
  id: 'sport-profile-1',
  display_name: 'Ana Entusiasta',
  city: 'Florianopolis',
  region: 'SC',
  sports: [
    {
      sport: { id: 'mod-1', name: 'Corrida' },
      level: 'Iniciante',
      goals: ['Praticar com regularidade'],
    },
  ],
  availability: [
    { weekday: 'sabado', starts_at: '08:00', ends_at: '10:00' },
  ],
})

assert.equal(profile.displayName, 'Ana Entusiasta')
assert.equal(profile.role, 'Entusiasta')
assert.equal(profile.locationLabel, 'Florianopolis, SC')
assert.equal(profile.primaryModality, 'Corrida')
assert.deepEqual(profile.modalities[0], {
  id: 'mod-1',
  name: 'Corrida',
  level: 'Iniciante',
  goal: 'Praticar com regularidade',
})
assert.deepEqual(profile.availability, ['sabado 08:00-10:00'])

const card = normalizeDiscoveryCard({
  id: 'card-1',
  sport_profile_id: 'sport-profile-1',
  distance_meters: 2600,
  next_action: 'request_participation',
  sport_session: {
    id: 'session-1',
    title: 'Volei de praia tecnico',
    modality: { id: 'mod-2', name: 'Volei de praia' },
    host_sport_profile: {
      id: 'host-1',
      display_name: 'Luiz Professor',
      role: 'Professor',
    },
    starts_at: '2026-07-13T19:00:00-03:00',
    location: { label: 'Beira-mar Norte', city: 'Florianopolis', region: 'SC' },
    entry_mode: 'curated',
    participation_status: 'pending',
    participant_count: 5,
  },
})

assert.equal(card.id, 'card-1')
assert.equal(card.sportProfileId, 'sport-profile-1')
assert.equal(card.distanceLabel, '2.6 km')
assert.equal(card.entryMode, 'curated')
assert.equal(card.nextAction, 'request_participation')
assert.equal(card.participationStatus, 'pending')
assert.equal(card.session.title, 'Volei de praia tecnico')
assert.equal(card.session.hostSportProfile.displayName, 'Luiz Professor')
assert.equal(card.session.modality.name, 'Volei de praia')
assert.equal(card.session.location.label, 'Beira-mar Norte')

assert.equal(normalizeDiscoveryCards({ data: [card.raw] }).length, 1)
