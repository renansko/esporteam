import { formatSessionDateTime } from './discoveryCard.js'
import { resolveSportIcon } from './sportIcons.js'

export const MATCH_STATUS_PRESENTATION = {
  confirmed: { label: 'Confirmado', icon: 'check', toneClass: 'match-status-confirmed' },
  pending: { label: 'Aguardando', icon: 'clock', toneClass: 'match-status-pending' },
  refused: { label: 'Recusado', icon: 'x', toneClass: 'match-status-refused' },
}

export function createParticipantMatchView(session) {
  const status = MATCH_STATUS_PRESENTATION[session?.participationState?.status] || MATCH_STATUS_PRESENTATION.refused
  const startsAtDate = session?.startsAt ? new Date(session.startsAt) : null
  const hasValidDate = startsAtDate && !Number.isNaN(startsAtDate.getTime())
  const timeLabel = hasValidDate
    ? startsAtDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
    : ''
  const dayLabel = hasValidDate
    ? startsAtDate.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
    : ''

  return {
    id: session.id,
    title: session.title,
    modality: session.modality?.name || 'Modalidade',
    modalityIcon: resolveSportIcon(session.modality),
    host: `${session.hostSportProfile?.role || 'Anfitriao da Sessao'} - ${session.hostSportProfile?.displayName || 'Anfitriao da Sessao'}`,
    dateTime: formatSessionDateTime(session.startsAt),
    startsAt: session.startsAt,
    startsAtDate: hasValidDate ? startsAtDate : null,
    timeLabel,
    dayLabel,
    location: session.location?.label || session.meetingPoint || 'Local a definir',
    statusId: session.participationState.status,
    status,
    pendingNotice: session.participationState.status === 'pending'
      ? 'Aguardando aprovacao do Anfitriao da Sessao.'
      : '',
    canOpen: ['confirmed', 'pending'].includes(session.participationState.status),
    session,
  }
}
