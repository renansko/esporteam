import { computed, reactive, ref } from 'vue'
import { listCompatibleSportSessions } from '../services/sportDiscovery.js'
import {
  DEFAULT_DISCOVERY_SESSION_FILTERS,
  createDefaultDiscoverySessionFilters,
} from '../features/participant/discoveryFilters.js'

function createDiscoveryError(err) {
  const status = err?.response?.status
  const offline = typeof navigator !== 'undefined' && navigator.onLine === false
  const validationMessage = err?.response?.data?.message

  if (offline || !err?.response) {
    return {
      title: 'Descoberta sem atualizacao',
      description: 'Nao foi possivel atualizar a Descoberta agora. Verifique sua conexao e tente novamente.',
      retryLabel: 'Tentar novamente',
    }
  }

  if (status === 422) {
    return {
      title: 'Filtros nao aplicados',
      description: validationMessage || 'Revise os filtros da Descoberta e tente novamente.',
      retryLabel: 'Tentar novamente',
    }
  }

  return {
    title: 'Descoberta indisponivel',
    description: validationMessage || 'A Descoberta nao conseguiu atualizar as Sessoes Esportivas compativeis.',
    retryLabel: 'Tentar novamente',
  }
}

export function useDiscoverySessions({ initialCards = [] } = {}) {
  const discoverySessionCards = ref([...initialCards])
  const discoverySessionsLoading = ref(false)
  const discoverySessionsError = ref(null)
  const discoverySessionFilters = reactive(createDefaultDiscoverySessionFilters())
  const hasDiscoverySessionFilters = computed(() => (
    discoverySessionFilters.sportSlug !== DEFAULT_DISCOVERY_SESSION_FILTERS.sportSlug
    || discoverySessionFilters.level !== DEFAULT_DISCOVERY_SESSION_FILTERS.level
    || discoverySessionFilters.goal !== DEFAULT_DISCOVERY_SESSION_FILTERS.goal
    || discoverySessionFilters.distanceKm !== DEFAULT_DISCOVERY_SESSION_FILTERS.distanceKm
    || discoverySessionFilters.weekday !== DEFAULT_DISCOVERY_SESSION_FILTERS.weekday
    || discoverySessionFilters.startsAt !== DEFAULT_DISCOVERY_SESSION_FILTERS.startsAt
    || discoverySessionFilters.endsAt !== DEFAULT_DISCOVERY_SESSION_FILTERS.endsAt
    || discoverySessionFilters.participationType !== DEFAULT_DISCOVERY_SESSION_FILTERS.participationType
  ))
  function setDiscoverySessionFilters(nextFilters = {}) {
    Object.assign(discoverySessionFilters, {
      ...createDefaultDiscoverySessionFilters(),
      ...nextFilters,
    })
  }

  function replaceDiscoverySessionCards(cards = []) {
    discoverySessionCards.value = Array.isArray(cards) ? [...cards] : []
  }

  async function loadCompatibleSportSessions(activeSportProfile, nextFilters = discoverySessionFilters) {
    discoverySessionsLoading.value = true
    discoverySessionsError.value = null

    try {
      const params = { ...nextFilters }
      if (activeSportProfile?.id) params.sport_profile_id = activeSportProfile.id

      const cards = await listCompatibleSportSessions(params, {
        useMockFallback: !activeSportProfile?.id,
      })
      replaceDiscoverySessionCards(cards)
    } catch (err) {
      discoverySessionsError.value = createDiscoveryError(err)
      replaceDiscoverySessionCards([])
    } finally {
      discoverySessionsLoading.value = false
    }
  }

  return {
    discoverySessionCards,
    discoverySessionsLoading,
    discoverySessionsError,
    discoverySessionFilters,
    hasDiscoverySessionFilters,
    setDiscoverySessionFilters,
    loadCompatibleSportSessions,
    replaceDiscoverySessionCards,
  }
}
