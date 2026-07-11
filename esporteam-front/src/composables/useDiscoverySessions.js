import { computed, reactive, ref } from 'vue'
import { joinSportSession, listCompatibleSportSessions } from '../services/sportDiscovery.js'
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

function validDiscoveryCards(cards) {
  return Array.isArray(cards)
    ? cards.filter(card => card && typeof card === 'object')
    : []
}

export function useDiscoverySessions({ initialCards = [], joinSession = joinSportSession } = {}) {
  const discoverySessionCards = ref(validDiscoveryCards(initialCards))
  const discoverySessionsLoading = ref(false)
  const discoverySessionsError = ref(null)
  const discoverySessionFilters = reactive(createDefaultDiscoverySessionFilters())
  const discoveryHistory = ref([])
  const discoveryActionLoading = ref(false)
  const discoveryActionError = ref(null)
  const discoveryActionFeedback = ref(null)
  const canUndoDiscovery = computed(() => discoveryHistory.value.length > 0)
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
    discoverySessionCards.value = validDiscoveryCards(cards)
    discoveryHistory.value = []
    discoveryActionFeedback.value = null
    discoveryActionError.value = null
  }

  function advanceCurrentCard(action) {
    const current = discoverySessionCards.value.shift()
    if (!current) return false
    discoveryHistory.value.push({ card: current, action })
    return true
  }

  function skipCurrentSession() {
    discoveryActionError.value = null
    discoveryActionFeedback.value = 'Sessao Esportiva pulada.'
    return advanceCurrentCard('skip')
  }

  function undoDiscoveryAction() {
    const previous = discoveryHistory.value.pop()
    if (!previous) return false
    discoverySessionCards.value.unshift(previous.card)
    discoveryActionFeedback.value = 'Sessao Esportiva restaurada.'
    discoveryActionError.value = null
    return true
  }

  async function showInterestInCurrentSession() {
    const card = discoverySessionCards.value[0]
    const sessionId = card?.session?.id ?? card?.id
    if (!card || !sessionId || discoveryActionLoading.value) return false
    const entryMode = card.entryMode ?? card.session?.entryMode
    const nextAction = card.nextAction ?? card.session?.nextAction
    if (entryMode === 'convite' || nextAction === 'indisponivel') {
      discoveryActionFeedback.value = 'Esta Sessao Esportiva nao esta disponivel para participacao publica.'
      return false
    }
    if (card.participationStatus || card.session?.participationStatus) {
      discoveryActionFeedback.value = 'Esta Sessao Esportiva ja recebeu uma acao.'
      return false
    }

    discoveryActionLoading.value = true
    discoveryActionError.value = null
    discoveryActionFeedback.value = null
    try {
      const updatedDetail = await joinSession(sessionId)
      updateSessionParticipation(updatedDetail)
      advanceCurrentCard('interest')
      discoveryActionFeedback.value = updatedDetail.participationState?.label || 'Interesse registrado.'
      return true
    } catch (err) {
      discoveryActionError.value = err?.response?.data?.message || err?.message || 'Nao foi possivel registrar seu interesse.'
      return false
    } finally {
      discoveryActionLoading.value = false
    }
  }

  function updateSessionParticipation(updatedDetail) {
    const sessionId = updatedDetail?.id
    if (!sessionId) return

    discoverySessionCards.value = discoverySessionCards.value.map(card => {
      const cardSessionId = card.session?.id ?? card.id
      if (String(cardSessionId) !== String(sessionId)) return card

      return {
        ...card,
        participationStatus: updatedDetail.participationState?.backendStatus ?? card.participationStatus,
        session: {
          ...card.session,
          participationStatus: updatedDetail.participationState?.backendStatus ?? card.session?.participationStatus,
          participation_status: updatedDetail.participationState?.backendStatus ?? card.session?.participation_status,
          participantCount: updatedDetail.participantCount ?? card.session?.participantCount,
          participant_count: updatedDetail.participantCount ?? card.session?.participant_count,
        },
      }
    })
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
    updateSessionParticipation,
    discoveryActionLoading,
    discoveryActionError,
    discoveryActionFeedback,
    canUndoDiscovery,
    skipCurrentSession,
    undoDiscoveryAction,
    showInterestInCurrentSession,
  }
}
