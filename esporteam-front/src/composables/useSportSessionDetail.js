import { computed, ref } from 'vue'
import {
  fetchSportSessionDetail,
  joinSportSession,
} from '../services/sportDiscovery.js'
import { createSportSessionDetailView } from '../features/participant/sessionDetail.js'

function detailError(err) {
  return err?.response?.data?.message
    || err?.message
    || 'Nao foi possivel carregar esta Sessao Esportiva.'
}

function sessionIdFromCard(card = {}) {
  return card.session?.id ?? card.id ?? null
}

export function useSportSessionDetail({
  fetchDetail = fetchSportSessionDetail,
  joinSession = joinSportSession,
  onParticipationUpdated = () => {},
  onParticipationConfirmed = () => {},
} = {}) {
  const selectedSessionCard = ref(null)
  const sportSessionDetail = ref(null)
  const sportSessionDetailLoading = ref(false)
  const sportSessionDetailError = ref(null)
  const sportSessionParticipationLoading = ref(false)
  const sportSessionParticipationFeedback = ref(null)
  const sportSessionParticipationFeedbackTone = ref(null)
  const isSportSessionDetailOpen = computed(() => Boolean(selectedSessionCard.value || sportSessionDetail.value))
  const isOpenParticipationDetail = computed(() => (
    sportSessionDetail.value?.entryMode === 'publica_direta'
    && sportSessionDetail.value?.nextAction === 'entrar'
  ))
  const isCuratedParticipationDetail = computed(() => (
    sportSessionDetail.value?.entryMode === 'publica_aprovacao'
    || sportSessionDetail.value?.entryRule === 'approval_required'
    || sportSessionDetail.value?.requiresApproval === true
  ))
  const isParticipationConfirmed = computed(() => (
    sportSessionDetail.value?.participationState?.status === 'confirmed'
  ))
  const isParticipationPending = computed(() => (
    sportSessionDetail.value?.participationState?.status === 'pending'
  ))
  const isParticipationResolved = computed(() => (
    ['confirmed', 'pending', 'refused'].includes(sportSessionDetail.value?.participationState?.status)
  ))
  const canSubmitParticipation = computed(() => (
    !sportSessionParticipationLoading.value
    && !isParticipationResolved.value
    && (isOpenParticipationDetail.value || isCuratedParticipationDetail.value)
  ))
  const sportSessionDetailView = computed(() => createSportSessionDetailView(sportSessionDetail.value, {
    confirmed: isParticipationConfirmed.value,
    participationFeedback: sportSessionParticipationFeedback.value,
    participationFeedbackTone: sportSessionParticipationFeedbackTone.value,
  }))

  async function openSportSessionDetail(card) {
    selectedSessionCard.value = card
    sportSessionDetail.value = null
    sportSessionDetailError.value = null
    sportSessionParticipationFeedback.value = null
    sportSessionParticipationFeedbackTone.value = null

    const sessionId = sessionIdFromCard(card)
    if (!sessionId) {
      sportSessionDetailError.value = 'Sessao Esportiva sem identificador.'
      return null
    }

    sportSessionDetailLoading.value = true
    try {
      sportSessionDetail.value = await fetchDetail(sessionId, {
        fallbackPayload: card?.session ?? card,
        useMockFallback: true,
      })
      return sportSessionDetail.value
    } catch (err) {
      sportSessionDetailError.value = detailError(err)
      return null
    } finally {
      sportSessionDetailLoading.value = false
    }
  }

  function closeSportSessionDetail() {
    selectedSessionCard.value = null
    sportSessionDetail.value = null
    sportSessionDetailError.value = null
    sportSessionParticipationFeedback.value = null
    sportSessionParticipationFeedbackTone.value = null
  }

  async function submitSportSessionParticipation() {
    const detail = sportSessionDetail.value
    if (!detail?.id || !canSubmitParticipation.value) return false

    sportSessionParticipationLoading.value = true
    sportSessionParticipationFeedback.value = null
    sportSessionParticipationFeedbackTone.value = null

    try {
      const updatedDetail = await joinSession(detail.id, {
        fallbackDetail: detail,
        useMockFallback: false,
      })
      sportSessionDetail.value = updatedDetail
      sportSessionParticipationFeedback.value = updatedDetail.participationState?.label || 'Confirmado'
      sportSessionParticipationFeedbackTone.value = updatedDetail.participationState?.status === 'pending'
        ? 'pending'
        : updatedDetail.participationState?.status === 'refused'
          ? 'refused'
          : 'success'
      onParticipationUpdated(updatedDetail)
      onParticipationConfirmed(updatedDetail)
      return true
    } catch (err) {
      sportSessionParticipationFeedback.value = detailError(err)
      sportSessionParticipationFeedbackTone.value = 'error'
      return false
    } finally {
      sportSessionParticipationLoading.value = false
    }
  }

  async function confirmOpenSportSessionParticipation() {
    return submitSportSessionParticipation()
  }

  return {
    selectedSessionCard,
    sportSessionDetail,
    sportSessionDetailLoading,
    sportSessionDetailError,
    sportSessionParticipationLoading,
    sportSessionParticipationFeedback,
    sportSessionParticipationFeedbackTone,
    sportSessionDetailView,
    isSportSessionDetailOpen,
    isOpenParticipationDetail,
    isCuratedParticipationDetail,
    isParticipationConfirmed,
    isParticipationPending,
    isParticipationResolved,
    canSubmitParticipation,
    openSportSessionDetail,
    closeSportSessionDetail,
    submitSportSessionParticipation,
    confirmOpenSportSessionParticipation,
  }
}
