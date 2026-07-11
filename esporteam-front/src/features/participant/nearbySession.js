import {
  formatSessionDateTime,
  resolveSessionEntryBadge,
} from './discoveryCard.js'
import { resolveSportIcon } from './sportIcons.js'

function firstValue(...values) {
  return values.find(value => value !== undefined && value !== null && value !== '')
}

function normalizeToken(value) {
  return String(value ?? '').trim().toLowerCase()
}

function formatDistanceKm(distanceKm) {
  if (typeof distanceKm !== 'number' || Number.isNaN(distanceKm)) return ''
  return `${distanceKm.toLocaleString('pt-BR', {
    minimumFractionDigits: distanceKm < 10 ? 1 : 0,
    maximumFractionDigits: 1,
  })} km`
}

function formatSessionTime(startsAt) {
  if (!startsAt) return 'Hora'

  const date = new Date(startsAt)
  if (Number.isNaN(date.getTime())) return 'Hora'

  return new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'America/Sao_Paulo',
  }).format(date)
}

function hashSessionId(value) {
  return String(value ?? '').split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
}

function deterministicPinPosition(card, index) {
  const hash = hashSessionId(card.session?.id ?? card.id)
  const columns = [18, 49, 76]
  const rows = [18, 34, 52, 70]

  return {
    left: `${columns[(hash + index) % columns.length]}%`,
    top: `${rows[(hash + index * 2) % rows.length]}%`,
  }
}

function resolveSummaryAction(card = {}) {
  const session = card.session ?? {}
  const status = normalizeToken(firstValue(
    card.participationStatus,
    session.participationStatus,
    session.participation_status,
    null,
  ))
  const entryMode = normalizeToken(firstValue(card.entryMode, session.entryMode, session.entry_mode))
  const entryRule = normalizeToken(firstValue(card.entryRule, session.entryRule, session.entry_rule))
  const requiresApproval = session.requiresApproval === true || session.requires_approval === true

  if (status === 'joined' || status === 'approved') {
    return { label: 'Confirmado', icon: 'check', disabled: true, toneClass: 'nearby-summary-primary-open' }
  }

  if (status === 'pending' || status === 'interested' || status === 'invited') {
    return { label: 'Aguardando aprovacao', icon: 'lock', disabled: true, toneClass: 'nearby-summary-primary-curated' }
  }

  if (status === 'declined' || status === 'removed') {
    return { label: 'Recusado', icon: 'x', disabled: true, toneClass: 'nearby-summary-primary-refused' }
  }

  if (entryMode === 'publica_direta') {
    return { label: 'Vou participar', icon: 'check', disabled: false, toneClass: 'nearby-summary-primary-open' }
  }

  if (entryMode === 'publica_aprovacao' || entryRule === 'approval_required' || requiresApproval) {
    return { label: 'Pedir para participar', icon: 'lock', disabled: false, toneClass: 'nearby-summary-primary-curated' }
  }

  return { label: 'Sem entrada publica', icon: 'x', disabled: true, toneClass: 'nearby-summary-primary-neutral' }
}

export function createNearbySportSessionView(card = {}, index = 0) {
  const session = card.session ?? {}
  const modality = session.modality ?? session.sport ?? {}
  const host = firstValue(card.host, session.hostSportProfile, session.host, {})
  const modalityLabel = firstValue(modality.name, modality.title, 'Modalidade')
  const modalityIcon = resolveSportIcon(modality)
  const title = firstValue(session.title, 'Sessao Esportiva')
  const hostLabel = firstValue(host.displayName, host.display_name, host.name, 'Anfitriao da Sessao')
  const hostRoleLabel = firstValue(host.role, 'Anfitriao da Sessao')
  const distanceKm = firstValue(card.distanceKm, typeof card.distanceMeters === 'number' ? card.distanceMeters / 1000 : null)
  const distanceLabel = firstValue(card.distanceLabel, formatDistanceKm(distanceKm), '')
  const locationLabel = firstValue(
    session.location?.label,
    session.locationLabelPublic,
    session.locationLabel,
    [session.location?.city, session.location?.region].filter(Boolean).join(', '),
    'Local aproximado',
  )
  const participantCount = firstValue(card.participantCount, session.participantCount, null)
  const participantCountLabel = typeof participantCount === 'number'
    ? `${participantCount} ${participantCount === 1 ? 'participante' : 'participantes'}`
    : ''
  const entryBadge = resolveSessionEntryBadge({
    entryMode: firstValue(card.entryMode, session.entryMode),
    entryRule: firstValue(card.entryRule, session.entryRule),
    requiresApproval: firstValue(session.requiresApproval, session.requires_approval, false),
  })
  const summaryAction = resolveSummaryAction(card)

  return {
    id: firstValue(card.id, session.id, `${title}-${index}`),
    rawCard: card,
    title,
    modalityLabel,
    modalityIcon,
    shortModalityLabel: modalityLabel.length > 12 ? `${modalityLabel.slice(0, 12).trim()}…` : modalityLabel,
    hostLabel,
    hostRoleLabel,
    dateTimeLabel: formatSessionDateTime(session.startsAt),
    timeCueLabel: formatSessionTime(session.startsAt),
    distanceLabel,
    locationLabel,
    participantCountLabel,
    entryBadge,
    summaryAction,
    pinPosition: deterministicPinPosition(card, index),
    listAriaLabel: [
      `Sessao Esportiva ${title}`,
      modalityLabel,
      formatSessionDateTime(session.startsAt),
      distanceLabel,
      locationLabel,
      `${hostRoleLabel} ${hostLabel}`,
      entryBadge.label,
    ].filter(Boolean).join('. '),
  }
}
