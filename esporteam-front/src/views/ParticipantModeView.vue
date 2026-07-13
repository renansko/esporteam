<script setup>
import { computed, onMounted, watch } from 'vue'
import { useAppStore } from '../stores/app'
import ParticipantShell from '../components/ParticipantShell.vue'
import { useDiscoverySessions } from '../composables/useDiscoverySessions'
import { useNearbySportSessions } from '../composables/useNearbySportSessions'
import { useSportSessionDetail } from '../composables/useSportSessionDetail'
import { useParticipantMatches } from '../composables/useParticipantMatches'
import { useSportProfileEditor } from '../composables/useSportProfileEditor'
import { useTeacherProfileEditor } from '../composables/useTeacherProfileEditor'

const store = useAppStore()
const activeSportProfile = computed(() => store.activeSportProfile)
const activeTeacherProfile = computed(() => store.teacherProfile)
const { draft: teacherProfileDraft, hourlyPrice: teacherHourlyPrice } = useTeacherProfileEditor(activeTeacherProfile)
const {
  draft: sportProfileDraft,
  loading: sportProfileSaving,
  error: sportProfileSaveError,
  validationErrors: sportProfileSaveErrors,
  success: sportProfileSaveSuccess,
  saveDraft: saveSportProfileDraft,
} = useSportProfileEditor(activeSportProfile, {
  save: (draft, context) => store.saveActiveSportProfile({ ...draft, ...context }),
  onSaved: reloadParticipantSessions,
})
const {
  matches: participantMatches,
  activeFilter: activeMatchFilter,
  loading: participantMatchesLoading,
  error: participantMatchesError,
  MATCH_FILTERS,
  loadParticipantMatches,
  setMatchFilter,
  upsertMatch,
} = useParticipantMatches()
const {
  discoverySessionCards,
  discoverySessionsLoading,
  discoverySessionsError,
  discoverySessionFilters,
  hasDiscoverySessionFilters,
  setDiscoverySessionFilters,
  loadCompatibleSportSessions,
  updateSessionParticipation,
  discoveryActionLoading,
  discoveryActionError,
  discoveryActionFeedback,
  canUndoDiscovery,
  skipCurrentSession,
  undoDiscoveryAction,
  showInterestInCurrentSession,
} = useDiscoverySessions()
const {
  nearbySessionCards,
  nearbySessionsLoading,
  nearbySessionsError,
  nearbySessionParticipationLoading,
  nearbySessionParticipationFeedback,
  nearbySessionParticipationFeedbackTone,
  clearNearbyParticipationFeedback,
  loadNearbySportSessions,
  submitNearbySessionParticipation,
  updateSessionParticipation: updateNearbySessionParticipation,
} = useNearbySportSessions({
  onParticipationUpdated: handleParticipationUpdated,
})
const {
  sportSessionDetailView,
  sportSessionDetailLoading,
  sportSessionDetailError,
  sportSessionParticipationLoading,
  sportSessionParticipationFeedbackTone,
  isSportSessionDetailOpen,
  isParticipationConfirmed,
  openSportSessionDetail,
  closeSportSessionDetail,
  submitSportSessionParticipation,
} = useSportSessionDetail({
  onParticipationUpdated: handleParticipationUpdated,
})

function handleParticipationUpdated(updatedDetail) {
  updateSessionParticipation(updatedDetail)
  updateNearbySessionParticipation(updatedDetail)
  store.upsertParticipantSportSession(updatedDetail)
  upsertMatch(updatedDetail)
}

function reloadParticipantSessions() {
  loadCompatibleSportSessions(store.activeSportProfile)
  loadNearbySportSessions(store.activeSportProfile, discoverySessionFilters)
}

function reloadDiscoverySessions() {
  loadCompatibleSportSessions(store.activeSportProfile)
}

function applyDiscoveryFilters(filters) {
  setDiscoverySessionFilters(filters)
  loadCompatibleSportSessions(store.activeSportProfile, filters)
  loadNearbySportSessions(store.activeSportProfile, filters)
}

function reloadNearbySessions() {
  loadNearbySportSessions(store.activeSportProfile, discoverySessionFilters)
}

onMounted(() => {
  reloadParticipantSessions()
  loadParticipantMatches()
})

watch(() => store.activeSportProfile?.id, () => {
  reloadParticipantSessions()
  loadParticipantMatches()
})

function saveProfile() {
  saveSportProfileDraft({ teacherProfile: store.teacherProfile ? teacherProfileDraft : null })
}

function updateTeacherProfileField(field, value) {
  teacherProfileDraft[field] = field === 'service_radius_km'
    ? (value === '' ? null : Number(value))
    : value
}

function updateTeacherHourlyPrice(value) {
  teacherHourlyPrice.value = value
}

function applyBioSuggestion(suggestion) {
  if (suggestion?.bio) sportProfileDraft.profile.bio = suggestion.bio
}

function acceptBioSuggestion(suggestion) {
  if (!suggestion?.bio) return
  // Exact acceptance has already persisted only the accepted bio on the API.
  // Updating this field keeps unrelated unsaved editor values intact.
  sportProfileDraft.profile.bio = suggestion.bio
}
</script>

<template>
  <ParticipantShell
    :discovery-cards="discoverySessionCards"
    :discovery-loading="discoverySessionsLoading"
    :discovery-error="discoverySessionsError"
    :discovery-filters="discoverySessionFilters"
    :has-discovery-filters="hasDiscoverySessionFilters"
    :discovery-action-loading="discoveryActionLoading"
    :discovery-action-error="discoveryActionError"
    :discovery-action-feedback="discoveryActionFeedback"
    :discovery-can-undo="canUndoDiscovery"
    :nearby-sessions="nearbySessionCards"
    :nearby-sessions-loading="nearbySessionsLoading"
    :nearby-sessions-error="nearbySessionsError"
    :nearby-session-participation-loading="nearbySessionParticipationLoading"
    :nearby-session-participation-feedback="nearbySessionParticipationFeedback"
    :nearby-session-participation-feedback-tone="nearbySessionParticipationFeedbackTone"
    :sport-session-detail-view="sportSessionDetailView"
    :sport-session-detail-open="isSportSessionDetailOpen"
    :sport-session-detail-loading="sportSessionDetailLoading"
    :sport-session-detail-error="sportSessionDetailError"
    :sport-session-participation-loading="sportSessionParticipationLoading"
    :sport-session-participation-confirmed="isParticipationConfirmed"
    :sport-session-participation-feedback-tone="sportSessionParticipationFeedbackTone"
    :participant-matches="participantMatches"
    :participant-match-filter="activeMatchFilter"
    :participant-matches-loading="participantMatchesLoading"
    :participant-matches-error="participantMatchesError"
    :participant-match-filters="MATCH_FILTERS"
    :sport-profile-draft="sportProfileDraft"
    :sport-profile-saving="sportProfileSaving"
    :sport-profile-save-error="sportProfileSaveError"
    :sport-profile-save-errors="sportProfileSaveErrors"
    :sport-profile-save-success="sportProfileSaveSuccess"
    :teacher-profile-draft="store.teacherProfile ? teacherProfileDraft : null"
    :teacher-hourly-price="teacherHourlyPrice"
    @apply-discovery-filters="applyDiscoveryFilters"
    @retry-discovery="reloadDiscoverySessions"
    @retry-nearby-sessions="reloadNearbySessions"
    @select-discovery-card="openSportSessionDetail"
    @skip-discovery-session="skipCurrentSession"
    @undo-discovery-action="undoDiscoveryAction"
    @show-interest-in-discovery-session="showInterestInCurrentSession"
    @close-sport-session-detail="closeSportSessionDetail"
    @select-nearby-session="clearNearbyParticipationFeedback"
    @close-nearby-session-summary="clearNearbyParticipationFeedback"
    @submit-nearby-session-participation="submitNearbySessionParticipation"
    @submit-sport-session-participation="submitSportSessionParticipation"
    @set-participant-match-filter="setMatchFilter"
    @select-participant-match="openSportSessionDetail"
    @retry-participant-matches="loadParticipantMatches"
    @save-sport-profile="saveProfile"
    @update-teacher-profile-field="updateTeacherProfileField"
    @update-teacher-hourly-price="updateTeacherHourlyPrice"
    @logout="store.logout"
    @apply-bio-suggestion="applyBioSuggestion"
    @accept-bio-suggestion="acceptBioSuggestion"
  />
</template>
