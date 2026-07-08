import { esporteamApi } from './api'
import { MOCK_ACTIVE_SPORT_PROFILE } from '../mock/sportDiscovery'

export function normalizeSportProfile(payload = {}) {
  return {
    id: payload.id ?? null,
    displayName: payload.display_name ?? payload.displayName ?? '',
    role: payload.role ?? 'Entusiasta',
    locationLabel: payload.location_label ?? payload.locationLabel ?? '',
    primaryModality: payload.primary_modality ?? payload.primaryModality ?? '',
    modalities: payload.modalities ?? [],
    availability: payload.availability ?? [],
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

export async function listCompatibleSportSessions(params = {}) {
  const { data } = await esporteamApi.get('/discovery/sessions', { params })
  return data?.data ?? []
}
