import { esporteamApi } from './api.js'
import {
  MOCK_ACTIVE_SPORT_PROFILE,
  MOCK_COMPATIBLE_SPORT_SESSIONS,
  MOCK_NEARBY_SPORT_SESSIONS,
  MOCK_PARTICIPANT_SPORT_SESSIONS,
  MOCK_SPORT_SESSION_DETAILS,
} from '../mock/sportDiscovery.js'

function firstValue(...values) {
  return values.find(value => value !== undefined && value !== null)
}

function publicDiscoveryRaw(payload = {}) {
  if (!payload || typeof payload !== 'object') return payload
  if (Array.isArray(payload)) return payload.map(publicDiscoveryRaw)

  const {
    capacity,
    remaining_capacity,
    remainingCapacity,
    remaining_slots,
    remainingSlots,
    available_slots,
    availableSlots,
    vacancy_status,
    vacancyStatus,
    ...publicPayload
  } = payload

  return Object.fromEntries(
    Object.entries(publicPayload).map(([key, value]) => [key, publicDiscoveryRaw(value)]),
  )
}

function present(value) {
  return value !== undefined && value !== null && value !== ''
}

export function normalizeDiscoverySessionFilters(filters = {}) {
  const normalized = {
    sport_id: firstValue(filters.sport_id, filters.sportId, null),
    sport_slug: firstValue(filters.sport_slug, filters.sportSlug, null),
    level: firstValue(filters.level, null),
    goal: firstValue(filters.goal, null),
    distance_km: firstValue(filters.distance_km, filters.distanceKm, null),
    weekday: firstValue(filters.weekday, null),
    starts_at: firstValue(filters.starts_at, filters.startsAt, null),
    ends_at: firstValue(filters.ends_at, filters.endsAt, null),
  }

  return Object.fromEntries(
    Object.entries(normalized).filter(([, value]) => present(value)),
  )
}

function isCuratedSession(card) {
  return card.entryMode === 'publica_aprovacao'
    || card.entryRule === 'approval_required'
    || card.session?.entryMode === 'publica_aprovacao'
    || card.session?.entryRule === 'approval_required'
}

function isOpenSession(card) {
  return card.entryMode === 'publica_direta'
    || card.session?.entryMode === 'publica_direta'
}

function matchesParticipationType(card, participationType) {
  if (!participationType || participationType === 'all') return true
  if (participationType === 'curated') return isCuratedSession(card)
  if (participationType === 'open') return isOpenSession(card)
  return true
}

function normalizeModality(payload = {}) {
  if (typeof payload === 'string') return { id: null, name: payload, slug: null, category: null }

  return {
    id: payload.id ?? null,
    name: payload.name ?? payload.title ?? 'Modalidade',
    slug: payload.slug ?? null,
    category: payload.category ?? null,
  }
}

function normalizeHostSportProfile(payload = {}) {
  return {
    id: payload.id ?? null,
    displayName: firstValue(payload.display_name, payload.displayName, payload.name, 'Anfitriao da Sessao'),
    role: payload.role ?? 'Anfitriao da Sessao',
  }
}

function normalizeLocation(payload = {}) {
  const label = firstValue(
    payload.location_label_public,
    payload.locationLabelPublic,
    payload.label,
    payload.location_label,
    payload.locationLabel,
    [payload.city, payload.region].filter(Boolean).join(', '),
    '',
  )

  return {
    label,
    city: payload.city ?? '',
    region: payload.region ?? '',
    latitude: firstValue(payload.latitude_approx, payload.latitudeApprox, payload.latitude, payload.lat, null),
    longitude: firstValue(payload.longitude_approx, payload.longitudeApprox, payload.longitude, payload.lng, null),
  }
}

function normalizeProfilePractice(practice = {}) {
  const modality = normalizeModality(practice.sport ?? practice.modality ?? practice)

  return {
    id: practice.id ?? modality.id,
    name: modality.name,
    level: firstValue(practice.level, practice.sport_level, practice.sportLevel, 'Nivel a definir'),
    goal: Array.isArray(practice.goals) ? practice.goals.join(', ') : firstValue(practice.goal, practice.objective, ''),
  }
}

export function normalizeSportProfile(payload = {}) {
  const sports = firstValue(payload.modalities, payload.sports, [])
  const availability = firstValue(payload.availability, payload.availability_windows, payload.availabilityWindows, [])
  const modalities = Array.isArray(sports) ? sports.map(normalizeProfilePractice) : []
  const location = normalizeLocation(payload)

  return {
    id: payload.id ?? null,
    displayName: firstValue(payload.display_name, payload.displayName, payload.name, ''),
    role: payload.role ?? 'Entusiasta',
    locationLabel: firstValue(payload.location_label, payload.locationLabel, location.label),
    primaryModality: firstValue(payload.primary_modality, payload.primaryModality, modalities[0]?.name, ''),
    modalities,
    availability: Array.isArray(availability)
      ? availability.map(window => {
        if (typeof window === 'string') return window
        return `${window.weekday} ${window.starts_at ?? window.startsAt}-${window.ends_at ?? window.endsAt}`
      })
      : [],
    raw: payload,
  }
}

function normalizeApprovedParticipant(payload = {}) {
  return {
    id: payload.id ?? null,
    displayName: firstValue(payload.display_name, payload.displayName, payload.name, 'Perfil Esportivo'),
    raw: publicDiscoveryRaw(payload),
  }
}

function normalizeList(value) {
  if (Array.isArray(value)) return value.filter(Boolean).map(item => String(item))
  if (typeof value === 'string' && value.trim()) return [value.trim()]
  return []
}

export function normalizeParticipationState(status) {
  switch (status) {
    case 'joined':
    case 'approved':
      return { status: 'confirmed', label: 'Confirmado', backendStatus: status }
    case 'pending':
    case 'interested':
    case 'invited':
      return { status: 'pending', label: 'Aguardando aprovacao', backendStatus: status }
    case 'declined':
    case 'removed':
      return { status: 'refused', label: 'Recusado', backendStatus: status }
    default:
      return { status: null, label: '', backendStatus: status ?? null }
  }
}

export function normalizeParticipantSportSessions(payload = []) {
  const items = Array.isArray(payload) ? payload : firstValue(payload.data, payload.items, [])
  return Array.isArray(items)
    ? items.map(item => normalizeSportSessionDetail(item)).filter(session => session.id && session.participationState.status)
    : []
}

function normalizeParticipationStatus(payload = {}, session = {}) {
  const directParticipation = firstValue(payload.participation, payload.current_participation, payload.currentParticipation, null)
  const sessionParticipants = firstValue(payload.session_participants, payload.sessionParticipants, payload.participants, [])
  const participationRecord = Array.isArray(directParticipation)
    ? directParticipation.find(item => firstValue(item.status, item.participation_status, item.participationStatus, null))
    : directParticipation
  const participantRecord = Array.isArray(sessionParticipants)
    ? sessionParticipants.find(item => firstValue(item.status, item.participation_status, item.participationStatus, null))
    : sessionParticipants

  return firstValue(
    participationRecord?.status,
    participationRecord?.participation_status,
    participationRecord?.participationStatus,
    participantRecord?.status,
    participantRecord?.participation_status,
    participantRecord?.participationStatus,
    payload.participation_status,
    payload.participationStatus,
    session.participationStatus,
    null,
  )
}

function normalizeLevel(session = {}) {
  const explicitLevel = firstValue(session.level, session.sport_level, session.sportLevel, null)
  if (explicitLevel) return explicitLevel

  const minLevel = firstValue(session.min_level, session.minLevel, null)
  const maxLevel = firstValue(session.max_level, session.maxLevel, null)

  if (minLevel && maxLevel && minLevel !== maxLevel) return `${minLevel} a ${maxLevel}`
  return firstValue(minLevel, maxLevel, 'Nivel a definir')
}

export function normalizeSportSession(payload = {}) {
  const session = payload.sport_session ?? payload.sportSession ?? payload.session ?? payload
  const approvedParticipants = firstValue(
    session.approved_participants,
    session.approvedParticipants,
    [],
  )

  return {
    id: session.id ?? null,
    title: firstValue(session.title, session.name, 'Sessao Esportiva'),
    modality: normalizeModality(session.modality ?? session.sport),
    hostSportProfile: normalizeHostSportProfile(
      session.host_sport_profile
      ?? session.hostSportProfile
      ?? session.host
      ?? payload.host,
    ),
    startsAt: firstValue(session.starts_at, session.startsAt, session.start_time, session.startTime, null),
    location: normalizeLocation({ ...session, ...(session.location ?? {}) }),
    entryMode: firstValue(session.entry_mode, session.entryMode, payload.entry_mode, payload.entryMode, 'open'),
    entryRule: firstValue(session.entry_rule, session.entryRule, payload.entry_rule, payload.entryRule, null),
    requiresApproval: firstValue(session.requires_approval, session.requiresApproval, null),
    nextAction: firstValue(session.next_action, session.nextAction, payload.next_action, payload.nextAction, null),
    participationStatus: firstValue(
      session.participation_status,
      session.participationStatus,
      payload.participation_status,
      payload.participationStatus,
      null,
    ),
    level: normalizeLevel(session),
    participantCount: firstValue(session.participant_count, session.participantCount, payload.participant_count, payload.participantCount, null),
    approvedParticipants: Array.isArray(approvedParticipants)
      ? approvedParticipants.map(normalizeApprovedParticipant)
      : [],
    raw: publicDiscoveryRaw(session),
  }
}

export function normalizeDiscoveryCard(payload = {}) {
  const session = normalizeSportSession(payload)
  const host = normalizeHostSportProfile(payload.host ?? session.hostSportProfile)
  const distanceMeters = firstValue(payload.distance_meters, payload.distanceMeters, null)
  const distanceKm = firstValue(
    payload.distance_km,
    payload.distanceKm,
    typeof distanceMeters === 'number' ? distanceMeters / 1000 : null,
  )

  return {
    id: payload.id ?? session.id,
    type: firstValue(payload.type, 'session'),
    score: firstValue(payload.score, null),
    reasons: Array.isArray(payload.reasons) ? payload.reasons : [],
    sportProfileId: firstValue(payload.sport_profile_id, payload.sportProfileId, null),
    host,
    session,
    distanceMeters,
    distanceKm,
    distanceLabel: firstValue(
      payload.distance_label,
      payload.distanceLabel,
      typeof distanceKm === 'number' ? `${distanceKm.toFixed(1)} km` : '',
    ),
    scoreLabel: firstValue(payload.score_label, payload.scoreLabel, ''),
    recommendationReason: firstValue(payload.recommendation_reason, payload.recommendationReason, ''),
    entryMode: firstValue(payload.entry_mode, payload.entryMode, session.entryMode),
    entryRule: firstValue(payload.entry_rule, payload.entryRule, session.entryRule),
    nextAction: firstValue(payload.next_action, payload.nextAction, session.nextAction),
    participantCount: firstValue(payload.participant_count, payload.participantCount, session.participantCount),
    participationStatus: firstValue(
      payload.participation_status,
      payload.participationStatus,
      session.participationStatus,
      null,
    ),
    safetyActions: Array.isArray(payload.safety_actions)
      ? payload.safety_actions
      : Array.isArray(payload.safetyActions) ? payload.safetyActions : [],
    raw: publicDiscoveryRaw(payload),
  }
}

export function normalizeDiscoveryCards(payload = []) {
  const items = Array.isArray(payload) ? payload : firstValue(payload.data, payload.items, [])
  return Array.isArray(items) ? items.filter(Boolean).map(normalizeDiscoveryCard) : []
}

export function normalizeSportSessionDetail(payload = {}) {
  const session = normalizeSportSession(payload)
  const rawSession = payload.session ?? payload.sport_session ?? payload
  const participants = firstValue(
    rawSession.participants,
    rawSession.approved_participants,
    rawSession.approvedParticipants,
    [],
  )
  const participation = firstValue(payload.participation, rawSession.participation, null)
  const participationStatus = normalizeParticipationStatus(rawSession, session)

  return {
    ...session,
    description: firstValue(rawSession.description, ''),
    meetingPoint: firstValue(
      rawSession.meeting_point,
      rawSession.meetingPoint,
      rawSession.location_label_public,
      rawSession.locationLabelPublic,
      session.location.label,
      '',
    ),
    rules: normalizeList(firstValue(rawSession.rules, rawSession.session_rules, rawSession.sessionRules, [])),
    equipment: normalizeList(firstValue(rawSession.equipment, rawSession.equipment_list, rawSession.equipmentList, [])),
    entryMode: firstValue(rawSession.entry_mode, rawSession.entryMode, session.entryMode),
    entryRule: firstValue(rawSession.entry_rule, rawSession.entryRule, session.entryRule),
    nextAction: firstValue(rawSession.next_action, rawSession.nextAction, session.nextAction),
    hostSportProfile: normalizeHostSportProfile(
      rawSession.creator
      ?? rawSession.host_sport_profile
      ?? rawSession.hostSportProfile
      ?? rawSession.host
      ?? session.hostSportProfile,
    ),
    participants: Array.isArray(participants)
      ? participants.map(normalizeApprovedParticipant)
      : [],
    participation,
    participationState: normalizeParticipationState(participationStatus),
    raw: publicDiscoveryRaw(rawSession),
  }
}

export async function fetchActiveSportProfile({ useMockFallback = true } = {}) {
  try {
    const { data } = await esporteamApi.get('/sport-profiles/me')
    return normalizeSportProfile(data?.data ?? data)
  } catch (err) {
    if (useMockFallback) return normalizeSportProfile(MOCK_ACTIVE_SPORT_PROFILE)
    throw err
  }
}

export async function listCompatibleSportSessions(params = {}, { useMockFallback = true } = {}) {
  const discoveryParams = normalizeDiscoverySessionFilters(params)
  const participationType = firstValue(params.participationType, params.participation_type, 'all')

  try {
    const { data } = await esporteamApi.get('/discovery', { params: { ...discoveryParams, mode: 'sessions' } })
    return normalizeDiscoveryCards(data?.data ?? data)
      .filter(card => matchesParticipationType(card, participationType))
  } catch (err) {
    if (useMockFallback) {
      return normalizeDiscoveryCards(MOCK_COMPATIBLE_SPORT_SESSIONS)
        .filter(card => matchesParticipationType(card, participationType))
    }
    throw err
  }
}

export async function listParticipantSportSessions({ useMockFallback = true } = {}) {
  try {
    const { data } = await esporteamApi.get('/profile/sessions')
    return normalizeParticipantSportSessions(data?.data ?? data)
  } catch (err) {
    if (useMockFallback) return normalizeParticipantSportSessions(MOCK_PARTICIPANT_SPORT_SESSIONS)
    throw err
  }
}

export async function listNearbySportSessions(params = {}, { useMockFallback = true } = {}) {
  const discoveryParams = normalizeDiscoverySessionFilters(params)
  const participationType = firstValue(params.participationType, params.participation_type, 'all')

  try {
    const { data } = await esporteamApi.get('/sessions', { params: discoveryParams })
    return normalizeDiscoveryCards(data?.data ?? data)
      .filter(card => matchesParticipationType(card, participationType))
  } catch (err) {
    if (useMockFallback) {
      return normalizeDiscoveryCards(MOCK_NEARBY_SPORT_SESSIONS)
        .filter(card => matchesParticipationType(card, participationType))
    }
    throw err
  }
}

function mockSportSessionDetail(sessionId, fallbackPayload = null) {
  const detail = MOCK_SPORT_SESSION_DETAILS.find(item => String(item.id) === String(sessionId))
  if (detail) return normalizeSportSessionDetail(detail)
  return fallbackPayload ? normalizeSportSessionDetail(fallbackPayload) : null
}

export async function fetchSportSessionDetail(sessionId, { fallbackPayload = null, useMockFallback = true } = {}) {
  if (!sessionId) throw new Error('session_id_required')

  try {
    const { data } = await esporteamApi.get(`/sessions/${sessionId}`)
    return normalizeSportSessionDetail(data?.data ?? data)
  } catch (err) {
    if (useMockFallback) {
      const detail = mockSportSessionDetail(sessionId, fallbackPayload)
      if (detail) return detail
    }
    throw err
  }
}

export async function joinSportSession(sessionId) {
  if (!sessionId) throw new Error('session_id_required')

  const { data } = await esporteamApi.post(`/sessions/${sessionId}/join`)
  return normalizeSportSessionDetail(data?.data ?? data)
}

export async function publishOneOffSportSession(payload, { idempotencyKey } = {}) {
  if (!idempotencyKey) throw new Error('idempotency_key_required')

  const { data } = await esporteamApi.post('/sessions/publish-one-off', payload, {
    headers: { 'Idempotency-Key': idempotencyKey },
  })
  return normalizeSportSessionDetail(data?.data ?? data)
}

export async function joinOpenSportSession(sessionId) {
  return joinSportSession(sessionId)
}

export async function requestCuratedSportSessionParticipation(sessionId) {
  return joinSportSession(sessionId)
}
