import { resolveSportIcon } from './sportIcons.js'

const CURATED_ENTRY_MODES = new Set([
  'curated',
  'publica_aprovacao',
  'approval_required',
])

const CURATED_ENTRY_RULES = new Set([
  'approval_required',
  'curated',
])

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

export function formatSessionDateTime(startsAt) {
  if (!startsAt) return 'Data a definir'

  const date = new Date(startsAt)
  if (Number.isNaN(date.getTime())) return 'Data a definir'

  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'America/Sao_Paulo',
  }).format(date)
}

export function resolveSessionEntryBadge({ entryMode, entryRule, requiresApproval } = {}) {
  const mode = normalizeToken(entryMode)
  const rule = normalizeToken(entryRule)
  const isCurated = requiresApproval === true || CURATED_ENTRY_MODES.has(mode) || CURATED_ENTRY_RULES.has(rule)

  if (isCurated) {
    return {
      kind: 'curated',
      icon: 'lock',
      label: 'Com curadoria',
      toneClass: 'session-entry-badge-curated',
      description: 'Anfitriao da Sessao aprova a participacao',
    }
  }

  return {
    kind: 'open',
    icon: 'check',
    label: 'Aberta',
    toneClass: 'session-entry-badge-open',
    description: 'Participacao confirmada ao demonstrar interesse',
  }
}

export function createSportSessionCardView(card = {}) {
  const session = card.session ?? {}
  const modality = session.modality ?? session.sport ?? {}
  const host = firstValue(card.host, session.hostSportProfile, session.host, {})
  const participantCount = firstValue(card.participantCount, session.participantCount, null)
  const distanceKm = firstValue(card.distanceKm, typeof card.distanceMeters === 'number' ? card.distanceMeters / 1000 : null)
  const distanceLabel = firstValue(card.distanceLabel, formatDistanceKm(distanceKm), 'Distancia a definir')
  const locationLabel = firstValue(
    session.location?.label,
    session.locationLabelPublic,
    session.locationLabel,
    [session.location?.city, session.location?.region].filter(Boolean).join(', '),
    '',
  )
  const levelLabel = firstValue(session.level, session.minLevel, session.maxLevel, 'Nivel a definir')
  const hostLabel = firstValue(host.displayName, host.display_name, host.name, 'Anfitriao da Sessao')
  const entryBadge = resolveSessionEntryBadge({
    entryMode: firstValue(card.entryMode, session.entryMode),
    entryRule: firstValue(card.entryRule, session.entryRule),
    requiresApproval: session.requiresApproval,
  })

  const title = firstValue(session.title, 'Sessao Esportiva')
  const modalityLabel = firstValue(modality.name, modality.title, 'Modalidade')
  const modalityIcon = resolveSportIcon(modality)
  const dateTimeLabel = formatSessionDateTime(session.startsAt)
  const participantCountLabel = typeof participantCount === 'number'
    ? `${participantCount} ${participantCount === 1 ? 'participante' : 'participantes'}`
    : ''
  const recommendationReason = firstValue(card.recommendationReason, card.scoreLabel, '')

  const accessibilityParts = [
    `Sessao Esportiva ${title}`,
    modalityLabel,
    `Anfitriao da Sessao ${hostLabel}`,
    distanceLabel,
    dateTimeLabel,
    `Nivel Esportivo ${levelLabel}`,
    participantCountLabel,
    entryBadge.label,
  ].filter(Boolean)

  return {
    id: firstValue(card.id, session.id, title),
    title,
    modalityLabel,
    modalityIcon,
    hostLabel,
    hostRoleLabel: firstValue(host.role, 'Anfitriao da Sessao'),
    distanceLabel,
    dateTimeLabel,
    levelLabel,
    locationLabel,
    participantCountLabel,
    recommendationReason,
    entryBadge,
    primaryActionLabel: entryBadge.kind === 'open' ? 'Vou participar' : 'Pedir para participar',
    primaryActionIcon: entryBadge.kind === 'open' ? 'check' : 'lock',
    canShowInterest: !['convite', 'indisponivel'].includes(card.entryMode)
      && !['convite', 'indisponivel'].includes(card.nextAction),
    accessibilityLabel: accessibilityParts.join('. '),
  }
}
