import { ref } from 'vue'
import { listCompatibleSportSessions } from '../services/sportDiscovery'

export function useDiscoverySessions() {
  const discoverySessionCards = ref([])
  const discoverySessionsLoading = ref(false)
  const discoverySessionsError = ref(null)

  async function loadCompatibleSportSessions(activeSportProfile) {
    discoverySessionsLoading.value = true
    discoverySessionsError.value = null

    try {
      discoverySessionCards.value = await listCompatibleSportSessions({
        sport_profile_id: activeSportProfile?.id,
      })
    } catch (err) {
      discoverySessionsError.value = err?.response?.data?.message || err?.message || 'discovery_sessions_failed'
      discoverySessionCards.value = []
    } finally {
      discoverySessionsLoading.value = false
    }
  }

  return {
    discoverySessionCards,
    discoverySessionsLoading,
    discoverySessionsError,
    loadCompatibleSportSessions,
  }
}
