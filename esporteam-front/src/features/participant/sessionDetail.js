import {
  formatSessionDateTime,
  resolveSessionEntryBadge,
} from './discoveryCard.js'
import { resolveSportIcon } from './sportIcons.js'

export function createSportSessionDetailView(detail, {
  confirmed = false,
  participationFeedback = null,
  participationFeedbackTone = null,
} = {}) {
  if (!detail) return null

  const entryBadge = resolveSessionEntryBadge({
    entryMode: detail.entryMode,
    entryRule: detail.entryRule,
    requiresApproval: detail.requiresApproval,
  })
  const isCurated = detail.entryMode === 'publica_aprovacao'
    || detail.entryMode === 'curated'
    || detail.entryRule === 'approval_required'
    || detail.requiresApproval === true
  const isOpen = detail.entryMode === 'publica_direta' && detail.nextAction === 'entrar'
  const participationState = detail.participationState ?? { status: null, label: '', backendStatus: null }
  const participantCountLabel = typeof detail.participantCount === 'number'
    ? `${detail.participantCount} ${detail.participantCount === 1 ? 'participante' : 'participantes'}`
    : ''
  const participants = confirmed && Array.isArray(detail.participants)
    ? detail.participants.map(participant => participant.displayName).filter(Boolean).slice(0, 4)
    : []
  const canSubmitParticipation = !participationState.status
    && (isOpen || (isCurated && ['pedir_vaga', 'request_approval', 'request_participation'].includes(detail.nextAction)))
  const primaryActionLabel = participationState.status === 'confirmed'
    ? 'Cancelar participação'
    : participationState.status === 'pending'
      ? 'Aguardando aprovacao'
      : participationState.status === 'refused'
        ? 'Recusado'
        : isCurated
          ? 'Pedir para participar'
          : isOpen
            ? 'Vou participar'
            : ''
  const primaryActionIcon = participationState.status === 'confirmed'
    ? 'check'
    : isCurated
      ? 'lock'
      : 'check'

  return {
    id: detail.id,
    title: detail.title,
    modalityLabel: detail.modality?.name || 'Modalidade',
    modalityIcon: resolveSportIcon(detail.modality),
    description: detail.description || 'Descricao a definir pelo Anfitriao da Sessao.',
    hostLabel: detail.hostSportProfile?.displayName || 'Anfitriao da Sessao',
    hostRoleLabel: detail.hostSportProfile?.role || 'Anfitriao da Sessao',
    dateTimeLabel: formatSessionDateTime(detail.startsAt),
    levelLabel: detail.level || 'Nivel a definir',
    locationLabel: detail.location?.label || detail.meetingPoint || 'Local a definir',
    meetingPoint: detail.meetingPoint || detail.location?.label || 'Ponto de encontro a definir',
    rules: detail.rules?.length ? detail.rules : ['Combinar ajustes com o Anfitriao da Sessao.'],
    equipment: detail.equipment?.length ? detail.equipment : ['Equipamento pessoal da Modalidade.'],
    participantCountLabel,
    participants,
    entryBadge,
    confirmed,
    participationFeedback,
    participationFeedbackTone,
    participationState,
    approvalNotice: isCurated
      ? 'O Anfitriao da Sessao revisa os pedidos antes de confirmar sua participacao.'
      : '',
    cancellationNotice: participationState.status === 'confirmed'
      ? 'O cancelamento ainda não é suportado pelo servidor.'
      : '',
    canSubmitParticipation,
    primaryActionLabel,
    primaryActionIcon,
    primaryActionToneClass: isCurated ? 'session-detail-primary-curated' : 'session-detail-primary-open',
  }
}
