import { formatSessionDateTime } from './discoveryCard.js'

export const MATCH_STATUS_PRESENTATION = {
  confirmed: { label: 'Confirmado', icon: 'check', toneClass: 'match-status-confirmed' },
  pending: { label: 'Aguardando', icon: 'clock', toneClass: 'match-status-pending' },
  refused: { label: 'Recusado', icon: 'x', toneClass: 'match-status-refused' },
}

export function createParticipantMatchView(session) {
  const status = MATCH_STATUS_PRESENTATION[session?.participationState?.status] || MATCH_STATUS_PRESENTATION.refused
  return {
    id: session.id,
    title: session.title,
    modality: session.modality?.name || 'Modalidade',
    host: `${session.hostSportProfile?.role || 'Anfitriao da Sessao'} · ${session.hostSportProfile?.displayName || 'Anfitriao da Sessao'}`,
    dateTime: formatSessionDateTime(session.startsAt),
    status,
    pendingNotice: session.participationState.status === 'pending'
      ? 'Aguardando aprovacao do Anfitriao da Sessao.'
      : '',
    canOpen: ['confirmed', 'pending'].includes(session.participationState.status),
    session,
  }
}
