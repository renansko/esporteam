import {
  formatSessionDateTime,
  resolveSessionEntryBadge,
} from './discoveryCard.js'

export function createSportSessionDetailView(detail, {
  confirmed = false,
  participationFeedback = null,
} = {}) {
  if (!detail) return null

  const entryBadge = resolveSessionEntryBadge({
    entryMode: detail.entryMode,
    entryRule: detail.entryRule,
    requiresApproval: detail.requiresApproval,
  })
  const participantCountLabel = typeof detail.participantCount === 'number'
    ? `${detail.participantCount} ${detail.participantCount === 1 ? 'participante' : 'participantes'}`
    : ''
  const participants = confirmed && Array.isArray(detail.participants)
    ? detail.participants.map(participant => participant.displayName).filter(Boolean).slice(0, 4)
    : []

  return {
    title: detail.title,
    modalityLabel: detail.modality?.name || 'Modalidade',
    description: detail.description || 'Descricao a definir pelo Anfitriao da Sessao.',
    hostLabel: detail.hostSportProfile?.displayName || 'Anfitriao da Sessao',
    hostRoleLabel: detail.hostSportProfile?.role || 'Anfitriao da Sessao',
    dateTimeLabel: formatSessionDateTime(detail.startsAt),
    levelLabel: detail.level || 'Nivel a definir',
    meetingPoint: detail.meetingPoint || detail.location?.label || 'Ponto de encontro a definir',
    rules: detail.rules?.length ? detail.rules : ['Combinar ajustes com o Anfitriao da Sessao.'],
    equipment: detail.equipment?.length ? detail.equipment : ['Equipamento pessoal da Modalidade.'],
    participantCountLabel,
    participants,
    entryBadge,
    confirmed,
    participationFeedback,
    canJoinOpen: detail.entryMode === 'publica_direta' && detail.nextAction === 'entrar',
  }
}
