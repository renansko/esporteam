import { esporteamApi } from './api.js'
import {
  MOCK_ACTIVE_SPORT_PROFILE,
  MOCK_COMPATIBLE_SPORT_SESSIONS,
} from '../mock/sportDiscovery.js'

function firstValue(...values) {
  return values.find(value => value !== undefined && value !== null)
}

function normalizeModality(payload = {}) {
  if (typeof payload === 'string') return { id: null, name: payload }

  return {
    id: payload.id ?? null,
    name: payload.name ?? payload.title ?? 'Modalidade',
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
    latitude: firstValue(payload.latitude, payload.lat, null),
    longitude: firstValue(payload.longitude, payload.lng, null),
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

export function normalizeSportSession(payload = {}) {
  const session = payload.sport_session ?? payload.sportSession ?? payload.session ?? payload

  return {
    id: session.id ?? null,
    title: firstValue(session.title, session.name, 'Sessao Esportiva'),
    modality: normalizeModality(session.modality ?? session.sport),
    hostSportProfile: normalizeHostSportProfile(session.host_sport_profile ?? session.hostSportProfile ?? session.host),
    startsAt: firstValue(session.starts_at, session.startsAt, session.start_time, session.startTime, null),
    location: normalizeLocation(session.location ?? session),
    entryMode: firstValue(session.entry_mode, session.entryMode, payload.entry_mode, payload.entryMode, 'open'),
    nextAction: firstValue(session.next_action, session.nextAction, payload.next_action, payload.nextAction, null),
    participationStatus: firstValue(
      session.participation_status,
      session.participationStatus,
      payload.participation_status,
      payload.participationStatus,
      null,
    ),
    level: firstValue(session.level, session.sport_level, session.sportLevel, 'Nivel a definir'),
    participantCount: firstValue(session.participant_count, session.participantCount, null),
    raw: session,
  }
}

export function normalizeDiscoveryCard(payload = {}) {
  const session = normalizeSportSession(payload)
  const distanceMeters = firstValue(payload.distance_meters, payload.distanceMeters, null)

  return {
    id: payload.id ?? session.id,
    sportProfileId: firstValue(payload.sport_profile_id, payload.sportProfileId, null),
    session,
    distanceMeters,
    distanceLabel: firstValue(
      payload.distance_label,
      payload.distanceLabel,
      typeof distanceMeters === 'number' ? `${(distanceMeters / 1000).toFixed(1)} km` : '',
    ),
    scoreLabel: firstValue(payload.score_label, payload.scoreLabel, ''),
    entryMode: firstValue(payload.entry_mode, payload.entryMode, session.entryMode),
    nextAction: firstValue(payload.next_action, payload.nextAction, session.nextAction),
    participationStatus: firstValue(payload.participation_status, payload.participationStatus, session.participationStatus),
    raw: payload,
  }
}

export function normalizeDiscoveryCards(payload = []) {
  const items = Array.isArray(payload) ? payload : firstValue(payload.data, payload.items, [])
  return Array.isArray(items) ? items.map(normalizeDiscoveryCard) : []
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
  try {
    const { data } = await esporteamApi.get('/discovery/sessions', { params })
    return normalizeDiscoveryCards(data?.data ?? data)
  } catch (err) {
    if (useMockFallback) return normalizeDiscoveryCards(MOCK_COMPATIBLE_SPORT_SESSIONS)
    throw err
  }
}
