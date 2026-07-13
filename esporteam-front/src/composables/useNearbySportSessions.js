import { ref } from 'vue'
import {
  joinSportSession,
  listNearbySportSessions,
} from '../services/sportDiscovery.js'
import { apiErrorMessage } from '../services/validation.js'

function createNearbyError(err) {
  const status = err?.response?.status
  const offline = typeof navigator !== 'undefined' && navigator.onLine === false
  const validationMessage = apiErrorMessage(err, status === 422
    ? 'Nao foi possivel usar a localizacao do seu Perfil Esportivo no mapa.'
    : 'Nao foi possivel carregar as Sessoes Esportivas proximas.')

  if (offline || !err?.response) {
    return {
      title: 'Mapa sem atualizacao',
      description: 'Nao foi possivel atualizar as Sessoes proximas agora. Verifique sua conexao e tente novamente.',
      retryLabel: 'Tentar novamente',
    }
  }

  if (status === 422) {
    return {
      title: 'Mapa nao atualizado',
      description: validationMessage,
      retryLabel: 'Tentar novamente',
    }
  }

  return {
    title: 'Sessoes proximas indisponiveis',
    description: validationMessage,
    retryLabel: 'Tentar novamente',
  }
}

function validNearbyCards(cards) {
  return Array.isArray(cards)
    ? cards.filter(card => card && typeof card === 'object')
    : []
}

export function useNearbySportSessions({
  listSessions = listNearbySportSessions,
  joinSession = joinSportSession,
  onParticipationUpdated = () => {},
} = {}) {
  const nearbySessionCards = ref([])
  const nearbySessionsLoading = ref(false)
  const nearbySessionsError = ref(null)
  const nearbySessionParticipationLoading = ref(false)
  const nearbySessionParticipationFeedback = ref(null)
  const nearbySessionParticipationFeedbackTone = ref(null)

  function replaceNearbySessionCards(cards = []) {
    nearbySessionCards.value = validNearbyCards(cards)
  }

  function clearNearbyParticipationFeedback() {
    nearbySessionParticipationFeedback.value = null
    nearbySessionParticipationFeedbackTone.value = null
  }

  function updateSessionParticipation(updatedDetail) {
    const sessionId = updatedDetail?.id
    if (!sessionId) return

    nearbySessionCards.value = nearbySessionCards.value.map(card => {
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

  async function loadNearbySportSessions(activeSportProfile, filters = {}) {
    nearbySessionsLoading.value = true

    try {
      const params = { ...filters }
      if (activeSportProfile?.id) params.sport_profile_id = activeSportProfile.id

      const cards = await listSessions(params, {
        useMockFallback: !activeSportProfile?.id,
      })
      nearbySessionsError.value = null
      replaceNearbySessionCards(cards)
    } catch (err) {
      nearbySessionsError.value = createNearbyError(err)
      replaceNearbySessionCards([])
    } finally {
      nearbySessionsLoading.value = false
    }
  }

  async function submitNearbySessionParticipation(card) {
    const sessionId = card?.session?.id ?? card?.id ?? null
    if (!sessionId) return false

    nearbySessionParticipationLoading.value = true
    clearNearbyParticipationFeedback()

    try {
      const updatedDetail = await joinSession(sessionId)
      updateSessionParticipation(updatedDetail)
      nearbySessionParticipationFeedback.value = updatedDetail.participationState?.label || 'Atualizado'
      nearbySessionParticipationFeedbackTone.value = updatedDetail.participationState?.status === 'pending'
        ? 'pending'
        : updatedDetail.participationState?.status === 'refused'
          ? 'refused'
          : 'success'
      onParticipationUpdated(updatedDetail)
      return true
    } catch (err) {
      nearbySessionParticipationFeedback.value = apiErrorMessage(err, 'Nao foi possivel atualizar a participacao nesta Sessao Esportiva.')
      nearbySessionParticipationFeedbackTone.value = 'error'
      return false
    } finally {
      nearbySessionParticipationLoading.value = false
    }
  }

  return {
    nearbySessionCards,
    nearbySessionsLoading,
    nearbySessionsError,
    nearbySessionParticipationLoading,
    nearbySessionParticipationFeedback,
    nearbySessionParticipationFeedbackTone,
    replaceNearbySessionCards,
    clearNearbyParticipationFeedback,
    updateSessionParticipation,
    loadNearbySportSessions,
    submitNearbySessionParticipation,
  }
}
