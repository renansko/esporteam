import assert from 'node:assert/strict'
import { useSportSessionDetail } from './useSportSessionDetail.js'

const opened = {
  id: 'session-open',
  title: 'Corrida aberta',
  entryMode: 'publica_direta',
  nextAction: 'entrar',
  participationState: { status: null, label: '', backendStatus: null },
  raw: {
    id: 'session-open',
    title: 'Corrida aberta',
    entry_mode: 'publica_direta',
    next_action: 'entrar',
  },
}
const confirmed = {
  ...opened,
  participationState: { status: 'confirmed', label: 'Confirmado', backendStatus: 'joined' },
}
const confirmedUpdates = []
const detail = useSportSessionDetail({
  fetchDetail: async (sessionId, options) => {
    assert.equal(sessionId, 'session-open')
    assert.equal(options.fallbackPayload.id, 'session-open')
    return opened
  },
  joinSession: async (sessionId, options) => {
    assert.equal(sessionId, 'session-open')
    assert.equal(options.fallbackDetail.id, 'session-open')
    assert.equal(options.useMockFallback, false)
    return confirmed
  },
  onParticipationUpdated: updated => confirmedUpdates.push(updated),
})

assert.equal(detail.isSportSessionDetailOpen.value, false)

await detail.openSportSessionDetail({
  id: 'card-open',
  session: { id: 'session-open', title: 'Corrida aberta' },
})

assert.equal(detail.isSportSessionDetailOpen.value, true)
assert.equal(detail.sportSessionDetail.value.title, 'Corrida aberta')
assert.equal(detail.isOpenParticipationDetail.value, true)
assert.equal(detail.isParticipationConfirmed.value, false)
assert.equal(detail.canSubmitParticipation.value, true)

assert.equal(await detail.submitSportSessionParticipation(), true)
assert.equal(detail.isParticipationConfirmed.value, true)
assert.equal(detail.sportSessionParticipationFeedback.value, 'Confirmado')
assert.equal(detail.sportSessionParticipationFeedbackTone.value, 'success')
assert.equal(detail.canSubmitParticipation.value, false)
assert.equal(confirmedUpdates[0].id, 'session-open')

detail.closeSportSessionDetail()
assert.equal(detail.isSportSessionDetailOpen.value, false)

const rejectedDetail = useSportSessionDetail({
  fetchDetail: async () => opened,
  joinSession: async () => {
    const err = new Error('duplicate')
    err.response = { data: { message: 'Perfil Esportivo ja confirmado nesta Sessao Esportiva.' } }
    throw err
  },
})

await rejectedDetail.openSportSessionDetail({
  session: { id: 'session-open', title: 'Corrida aberta' },
})
assert.equal(await rejectedDetail.submitSportSessionParticipation(), false)
assert.equal(rejectedDetail.isSportSessionDetailOpen.value, true)
assert.equal(rejectedDetail.isParticipationConfirmed.value, false)
assert.equal(rejectedDetail.sportSessionParticipationFeedback.value, 'Perfil Esportivo ja confirmado nesta Sessao Esportiva.')
assert.equal(rejectedDetail.sportSessionParticipationFeedbackTone.value, 'error')

const curatedOpened = {
  id: 'session-curated',
  title: 'Volei tecnico',
  entryMode: 'publica_aprovacao',
  entryRule: 'approval_required',
  nextAction: 'pedir_vaga',
  requiresApproval: true,
  participationState: { status: null, label: '', backendStatus: null },
  raw: {
    id: 'session-curated',
    title: 'Volei tecnico',
    entry_mode: 'publica_aprovacao',
    next_action: 'pedir_vaga',
  },
}
const curatedPending = {
  ...curatedOpened,
  participationState: { status: 'pending', label: 'Aguardando aprovacao', backendStatus: 'interested' },
}
let curatedJoinCalls = 0
const curatedDetail = useSportSessionDetail({
  fetchDetail: async () => curatedOpened,
  joinSession: async () => {
    curatedJoinCalls += 1
    return curatedPending
  },
})

await curatedDetail.openSportSessionDetail({
  session: { id: 'session-curated', title: 'Volei tecnico' },
})
assert.equal(curatedDetail.isCuratedParticipationDetail.value, true)
assert.equal(curatedDetail.canSubmitParticipation.value, true)
assert.equal(await curatedDetail.submitSportSessionParticipation(), true)
assert.equal(curatedJoinCalls, 1)
assert.equal(curatedDetail.isParticipationPending.value, true)
assert.equal(curatedDetail.sportSessionParticipationFeedback.value, 'Aguardando aprovacao')
assert.equal(curatedDetail.sportSessionParticipationFeedbackTone.value, 'pending')
assert.equal(curatedDetail.canSubmitParticipation.value, false)
assert.equal(await curatedDetail.submitSportSessionParticipation(), false)
assert.equal(curatedJoinCalls, 1)
