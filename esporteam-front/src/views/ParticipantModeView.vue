<script setup>
import { onMounted, watch } from 'vue'
import { useAppStore } from '../stores/app'
import ParticipantShell from '../components/ParticipantShell.vue'
import { useDiscoverySessions } from '../composables/useDiscoverySessions'
import { useSportSessionDetail } from '../composables/useSportSessionDetail'

const store = useAppStore()
const {
  discoverySessionCards,
  discoverySessionsLoading,
  discoverySessionsError,
  discoverySessionFilters,
  hasDiscoverySessionFilters,
  setDiscoverySessionFilters,
  loadCompatibleSportSessions,
  updateSessionParticipation,
} = useDiscoverySessions()
const {
  sportSessionDetailView,
  sportSessionDetailLoading,
  sportSessionDetailError,
  sportSessionParticipationLoading,
  isSportSessionDetailOpen,
  isParticipationConfirmed,
  openSportSessionDetail,
  closeSportSessionDetail,
  confirmOpenSportSessionParticipation,
} = useSportSessionDetail({
  onParticipationConfirmed: (updatedDetail) => {
    updateSessionParticipation(updatedDetail)
    store.upsertParticipantSportSession(updatedDetail)
  },
})

onMounted(() => {
  loadCompatibleSportSessions(store.activeSportProfile)
})

watch(() => store.activeSportProfile?.id, () => {
  loadCompatibleSportSessions(store.activeSportProfile)
})

function applyDiscoveryFilters(filters) {
  setDiscoverySessionFilters(filters)
  loadCompatibleSportSessions(store.activeSportProfile)
}
</script>

<template>
  <ParticipantShell
    :discovery-cards="discoverySessionCards"
    :discovery-loading="discoverySessionsLoading"
    :discovery-error="discoverySessionsError"
    :discovery-filters="discoverySessionFilters"
    :has-discovery-filters="hasDiscoverySessionFilters"
    :sport-session-detail-view="sportSessionDetailView"
    :sport-session-detail-open="isSportSessionDetailOpen"
    :sport-session-detail-loading="sportSessionDetailLoading"
    :sport-session-detail-error="sportSessionDetailError"
    :sport-session-participation-loading="sportSessionParticipationLoading"
    :sport-session-participation-confirmed="isParticipationConfirmed"
    @apply-discovery-filters="applyDiscoveryFilters"
    @retry-discovery="loadCompatibleSportSessions(store.activeSportProfile)"
    @select-discovery-card="openSportSessionDetail"
    @close-sport-session-detail="closeSportSessionDetail"
    @join-open-sport-session="confirmOpenSportSessionParticipation"
  />
</template>
