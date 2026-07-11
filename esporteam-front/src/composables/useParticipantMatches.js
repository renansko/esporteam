import { computed, ref } from 'vue'
import { listParticipantSportSessions } from '../services/sportDiscovery.js'

const MATCH_FILTERS = [
  { id: 'all', label: 'Todas' },
  { id: 'confirmed', label: 'Confirmado' },
  { id: 'pending', label: 'Aguardando' },
  { id: 'refused', label: 'Recusado' },
]

export function useParticipantMatches({ listSessions = listParticipantSportSessions } = {}) {
  const matches = ref([])
  const activeFilter = ref('all')
  const loading = ref(false)
  const error = ref(null)
  const filteredMatches = computed(() => activeFilter.value === 'all'
    ? matches.value
    : matches.value.filter(item => item.participationState.status === activeFilter.value))

  async function loadParticipantMatches() {
    loading.value = true
    error.value = null
    try {
      matches.value = await listSessions({ useMockFallback: true })
    } catch (err) {
      error.value = err?.response?.data?.message || err?.message || 'Nao foi possivel carregar suas Partidas.'
      matches.value = []
    } finally {
      loading.value = false
    }
  }

  function setMatchFilter(filter) {
    if (MATCH_FILTERS.some(option => option.id === filter)) activeFilter.value = filter
  }

  function upsertMatch(session) {
    if (!session?.id || !session.participationState?.status) return
    const index = matches.value.findIndex(item => String(item.id) === String(session.id))
    if (index >= 0) matches.value.splice(index, 1, session)
    else matches.value.unshift(session)
  }

  return { matches, filteredMatches, activeFilter, loading, error, MATCH_FILTERS, loadParticipantMatches, setMatchFilter, upsertMatch }
}
