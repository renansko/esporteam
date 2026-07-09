<script setup>
import { onMounted, watch } from 'vue'
import { useAppStore } from '../stores/app'
import ParticipantShell from '../components/ParticipantShell.vue'
import { useDiscoverySessions } from '../composables/useDiscoverySessions'

const store = useAppStore()
const {
  discoverySessionCards,
  discoverySessionsLoading,
  discoverySessionsError,
  discoverySessionFilters,
  hasDiscoverySessionFilters,
  setDiscoverySessionFilters,
  loadCompatibleSportSessions,
} = useDiscoverySessions()

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
    @apply-discovery-filters="applyDiscoveryFilters"
    @retry-discovery="loadCompatibleSportSessions(store.activeSportProfile)"
  />
</template>
