import assert from 'node:assert/strict'
import { createParticipantMatchView } from '../features/participant/matches.js'
import { useParticipantMatches } from './useParticipantMatches.js'

const sessions = [
  { id: 'confirmed', title: 'Corrida', startsAt: '2026-07-12T08:00:00-03:00', modality: { name: 'Corrida' }, hostSportProfile: { role: 'Organizador', displayName: 'Marina' }, participationState: { status: 'confirmed', label: 'Confirmado' } },
  { id: 'pending', title: 'Volei', startsAt: '2026-07-13T19:00:00-03:00', modality: { name: 'Volei' }, hostSportProfile: { role: 'Professor', displayName: 'Luiz' }, participationState: { status: 'pending', label: 'Aguardando aprovacao' } },
  { id: 'refused', title: 'Futebol', startsAt: '2026-07-14T20:00:00-03:00', modality: { name: 'Futebol' }, hostSportProfile: { role: 'Organizador', displayName: 'Joao' }, participationState: { status: 'refused', label: 'Recusado' } },
]

const matches = useParticipantMatches({ listSessions: async () => sessions })
await matches.loadParticipantMatches()
assert.equal(matches.filteredMatches.value.length, 3)

matches.setMatchFilter('pending')
assert.deepEqual(matches.filteredMatches.value.map(item => item.id), ['pending'])
assert.equal(createParticipantMatchView(sessions[1]).pendingNotice, 'Aguardando aprovacao do Anfitriao da Sessao.')
assert.equal(createParticipantMatchView(sessions[1]).canOpen, true)
assert.equal(createParticipantMatchView(sessions[2]).canOpen, false)

matches.setMatchFilter('refused')
assert.deepEqual(matches.filteredMatches.value.map(item => item.id), ['refused'])
