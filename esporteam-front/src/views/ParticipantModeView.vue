<script setup>
import { onMounted, watch } from 'vue'
import { useAppStore } from '../stores/app'
import ParticipantShell from '../components/ParticipantShell.vue'
import { useDiscoverySessions } from '../composables/useDiscoverySessions'

const store = useAppStore()
const {
  discoverySessionCards,
  loadCompatibleSportSessions,
} = useDiscoverySessions()

onMounted(() => {
  loadCompatibleSportSessions(store.activeSportProfile)
})

watch(() => store.activeSportProfile?.id, () => {
  loadCompatibleSportSessions(store.activeSportProfile)
})
</script>

<template>
  <ParticipantShell :discovery-cards="discoverySessionCards" />
</template>
