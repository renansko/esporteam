<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { useDelayedLoading } from '../composables/useDelayedLoading'
import { useAppStore } from '../stores/app'
import { createSportSessionCardView } from '../features/participant/discoveryCard'
import { createNearbySportSessionView } from '../features/participant/nearbySession'
import {
  DISCOVERY_GOAL_OPTIONS,
  DISCOVERY_LEVEL_OPTIONS,
  DISCOVERY_PARTICIPATION_TYPE_OPTIONS,
  DISCOVERY_SPORT_OPTIONS,
  DISCOVERY_WEEKDAY_OPTIONS,
  createDefaultDiscoverySessionFilters,
  discoveryFilterOptionLabel,
} from '../features/participant/discoveryFilters'
import { PARTICIPANT_TABS, resolveParticipantTab } from '../features/participant/shell'
import Icon from './Icon.vue'
import BottomSheet from './BottomSheet.vue'
import ProfileBioAssistant from './ProfileBioAssistant.vue'
import Skeleton from './Skeleton.vue'
import NearbySessionsMap from './NearbySessionsMap.vue'
import { createParticipantMatchView } from '../features/participant/matches'
import { firstValidationError } from '../services/validation'

const props = defineProps({
  discoveryCards: { type: Array, default: null },
  discoveryLoading: { type: Boolean, default: false },
  discoveryError: { type: Object, default: null },
  discoveryFilters: { type: Object, default: () => ({}) },
  hasDiscoveryFilters: { type: Boolean, default: false },
  discoveryActionLoading: { type: Boolean, default: false },
  discoveryActionError: { type: String, default: null },
  discoveryActionFeedback: { type: String, default: null },
  discoveryCanUndo: { type: Boolean, default: false },
  nearbySessions: { type: Array, default: null },
  nearbySessionsLoading: { type: Boolean, default: false },
  nearbySessionsError: { type: Object, default: null },
  nearbySurfaceMode: { type: String, default: null },
  nearbySelectedSessionId: { type: String, default: null },
  nearbySessionParticipationLoading: { type: Boolean, default: false },
  nearbySessionParticipationFeedback: { type: String, default: null },
  nearbySessionParticipationFeedbackTone: { type: String, default: null },
  sportSessionDetailView: { type: Object, default: null },
  sportSessionDetailOpen: { type: Boolean, default: false },
  sportSessionDetailLoading: { type: Boolean, default: false },
  sportSessionDetailError: { type: String, default: null },
  sportSessionParticipationLoading: { type: Boolean, default: false },
  sportSessionParticipationConfirmed: { type: Boolean, default: false },
  sportSessionParticipationFeedbackTone: { type: String, default: null },
  participantMatches: { type: Array, default: () => [] },
  participantMatchFilter: { type: String, default: 'all' },
  participantMatchesLoading: { type: Boolean, default: false },
  participantMatchesError: { type: String, default: null },
  participantMatchFilters: { type: Array, default: () => [] },
  sportProfileDraft: { type: Object, default: null },
  sportProfileSaving: { type: Boolean, default: false },
  sportProfileSaveError: { type: String, default: null },
  sportProfileSaveErrors: { type: Object, default: () => ({}) },
  sportProfileSaveSuccess: { type: Boolean, default: false },
  teacherProfileDraft: { type: Object, default: null },
  teacherHourlyPrice: { type: [Number, String], default: '' },
})
const emit = defineEmits([
  'applyDiscoveryFilters',
  'retryDiscovery',
  'retryNearbySessions',
  'selectDiscoveryCard',
  'skipDiscoverySession',
  'undoDiscoveryAction',
  'showInterestInDiscoverySession',
  'selectNearbySession',
  'closeNearbySessionSummary',
  'submitNearbySessionParticipation',
  'closeSportSessionDetail',
  'submitSportSessionParticipation',
  'setParticipantMatchFilter',
  'selectParticipantMatch',
  'retryParticipantMatches',
  'saveSportProfile',
  'updateTeacherProfileField',
  'updateTeacherHourlyPrice',
  'logout',
  'applyBioSuggestion',
  'acceptBioSuggestion',
])

const store = useAppStore()
const discoveryFiltersOpen = ref(false)
const draftFilters = reactive(createDefaultDiscoverySessionFilters())
const availabilityDayPickerIndex = ref(null)
const locationEditorOpen = ref(false)
const sportPracticesField = ref(null)
const bioContextInvalid = ref(false)
const practicesEditorOpen = ref(false)
const availabilityEditorOpen = ref(false)
const nearbySurface = ref(props.nearbySurfaceMode || 'map')
const selectedNearbySessionId = ref(
  props.nearbySelectedSessionId
    || (props.nearbySessions?.[0] ? createNearbySportSessionView(props.nearbySessions[0], 0).id : null),
)
const discoverySkeletonVisible = useDelayedLoading(() => props.discoveryLoading)
const nearbySkeletonVisible = useDelayedLoading(() => props.nearbySessionsLoading)
const matchesSkeletonVisible = useDelayedLoading(() => props.participantMatchesLoading)
const detailSkeletonVisible = useDelayedLoading(() => props.sportSessionDetailLoading)

watch(() => props.discoveryFilters, (filters = {}) => {
  Object.assign(draftFilters, {
    ...createDefaultDiscoverySessionFilters(),
    ...filters,
  })
}, { immediate: true, deep: true })

watch(() => props.nearbySessions, (sessions = []) => {
  if (!selectedNearbySessionId.value && sessions.length) {
    selectedNearbySessionId.value = createNearbySportSessionView(sessions[0], 0).id
    return
  }
  if (!selectedNearbySessionId.value) return

  const stillExists = sessions.some((card, index) => {
    const id = createNearbySportSessionView(card, index).id
    return String(id) === String(selectedNearbySessionId.value)
  })

  if (!stillExists) selectedNearbySessionId.value = null
}, { deep: true })

watch(() => props.nearbySurfaceMode, (mode) => {
  if (mode === 'map' || mode === 'list') nearbySurface.value = mode
})

watch(() => props.nearbySelectedSessionId, (sessionId) => {
  selectedNearbySessionId.value = sessionId || null
})

const activeTab = computed(() => resolveParticipantTab(store.participantTab))
const isDiscoverTab = computed(() => activeTab.value.id === 'discover')
const isMatchesTab = computed(() => activeTab.value.id === 'matches')
const isProfileTab = computed(() => activeTab.value.id === 'profile')
const isTeacher = computed(() => Boolean(props.teacherProfileDraft))
const participantMatchViews = computed(() => props.participantMatches.map(createParticipantMatchView))
const now = new Date('2026-07-11T00:00:00-03:00')
const upcomingConfirmedMatches = computed(() => participantMatchViews.value
  .filter(match => match.statusId === 'confirmed')
  .filter(match => !match.startsAtDate || match.startsAtDate >= now)
  .sort((a, b) => {
    if (!a.startsAtDate || !b.startsAtDate) return 0
    return a.startsAtDate - b.startsAtDate
  }))
const agendaPreviewMatches = computed(() => upcomingConfirmedMatches.value.slice(0, 5))
const historyMatches = computed(() => participantMatchViews.value
  .filter(match => !upcomingConfirmedMatches.value.some(upcoming => upcoming.id === match.id)))
const filteredHistoryMatches = computed(() => props.participantMatchFilter === 'all'
  ? historyMatches.value
  : historyMatches.value.filter(match => match.statusId === props.participantMatchFilter))
const isMapTab = computed(() => activeTab.value.id === 'map')
const primaryDiscoveryCard = computed(() => (
  props.discoveryCards?.[0]
    ? createSportSessionCardView(props.discoveryCards[0])
    : null
))
const primaryRawDiscoveryCard = computed(() => props.discoveryCards?.[0] ?? null)
const activeFilterSummary = computed(() => {
  if (!props.hasDiscoveryFilters) return ''

  const parts = [
    discoveryFilterOptionLabel(DISCOVERY_SPORT_OPTIONS, props.discoveryFilters.sportSlug),
    props.discoveryFilters.distanceKm ? `${props.discoveryFilters.distanceKm} km` : '',
    discoveryFilterOptionLabel(DISCOVERY_LEVEL_OPTIONS, props.discoveryFilters.level),
    discoveryFilterOptionLabel(DISCOVERY_GOAL_OPTIONS, props.discoveryFilters.goal),
    discoveryFilterOptionLabel(DISCOVERY_WEEKDAY_OPTIONS, props.discoveryFilters.weekday),
    props.discoveryFilters.startsAt && props.discoveryFilters.endsAt
      ? `${props.discoveryFilters.startsAt}-${props.discoveryFilters.endsAt}`
      : '',
    discoveryFilterOptionLabel(DISCOVERY_PARTICIPATION_TYPE_OPTIONS, props.discoveryFilters.participationType),
  ].filter(Boolean)

  return parts.length ? `Filtros ativos: ${parts.join(' · ')}` : ''
})

function applyFilters() {
  emit('applyDiscoveryFilters', { ...draftFilters })
  discoveryFiltersOpen.value = false
}

function clearFilters() {
  emit('applyDiscoveryFilters', createDefaultDiscoverySessionFilters())
  discoveryFiltersOpen.value = false
}
const pointerStartX = ref(null)
const discoveryDragX = ref(0)
const discoveryDragging = ref(false)
function beginDiscoveryPointer(event) {
  pointerStartX.value = event.clientX
  discoveryDragX.value = 0
  discoveryDragging.value = true
  event.currentTarget.setPointerCapture?.(event.pointerId)
}
function moveDiscoveryPointer(event) {
  if (pointerStartX.value === null) return
  discoveryDragX.value = event.clientX - pointerStartX.value
}
function endDiscoveryPointer(event) {
  if (pointerStartX.value === null) return
  const delta = event.clientX - pointerStartX.value
  pointerStartX.value = null
  discoveryDragging.value = false
  discoveryDragX.value = 0
  if (Math.abs(delta) < 72) return
  if (delta < 0) emit('skipDiscoverySession')
  else if (primaryDiscoveryCard.value?.canShowInterest) emit('showInterestInDiscoverySession')
}
const nearbySessionViews = computed(() => (
  Array.isArray(props.nearbySessions)
    ? props.nearbySessions.map((card, index) => createNearbySportSessionView(card, index))
    : []
))
const selectedNearbySessionView = computed(() => (
  nearbySessionViews.value.find(item => item.id === selectedNearbySessionId.value) || null
))

const sportProfile = computed(() => store.activeSportProfile)
const hasProfileData = computed(() => {
  const draft = props.sportProfileDraft
  if (!draft) return false

  return Boolean(
    draft.profile?.display_name
    || draft.profile?.bio
    || draft.profile?.city
    || draft.profile?.region
    || draft.sports?.length
    || draft.availability?.length,
  )
})
const isEditing = ref(!hasProfileData.value)
const locationLabel = computed(() => formatLocation(props.sportProfileDraft?.profile))
const locationDraft = computed({
  get: () => locationLabel.value,
  set: (value) => {
    const [city = '', region = ''] = String(value).split(/\s+-\s+/, 2)
    if (!props.sportProfileDraft?.profile) return
    props.sportProfileDraft.profile.city = city.trim()
    props.sportProfileDraft.profile.region = region.trim()
  },
})
const initials = computed(() => {
  const name = sportProfile.value?.displayName || ''
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]?.toUpperCase()).join('') || 'PE'
})
function openAvailabilityDayPicker(index) {
  availabilityDayPickerIndex.value = index
}

function selectAvailabilityDay(day) {
  const window = props.sportProfileDraft?.availability?.[availabilityDayPickerIndex.value]
  if (window) window.weekday = day
  availabilityDayPickerIndex.value = null
}

function selectNearbySession(sessionId) {
  selectedNearbySessionId.value = sessionId
  emit('selectNearbySession')
}

function closeNearbySessionSummary() {
  selectedNearbySessionId.value = null
  emit('closeNearbySessionSummary')
}

const weekdayLabels = ['Domingo', 'Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado']
function addAvailability() {
  if (props.sportProfileDraft?.availability) props.sportProfileDraft.availability.push({ weekday: 6, starts_at: '08:00', ends_at: '10:00' })
}
function removeAvailability(index) {
  props.sportProfileDraft?.availability?.splice(index, 1)
}
function updateGoals(practice, event) {
  practice.goals = event.target.value.split(',').map(goal => goal.trim()).filter(Boolean)
}

function formatLocation(profile = {}) {
  return [profile?.city, profile?.region].filter(Boolean).join(' - ')
}

function availabilityLabel(window) {
  return `${weekdayLabels[window.weekday] || 'Dia a definir'} · ${window.starts_at || '--:--'}–${window.ends_at || '--:--'}`
}

function startEditing(section = null) {
  isEditing.value = true
  practicesEditorOpen.value = section === 'practices'
  availabilityEditorOpen.value = section === 'availability'
}

function saveProfile() {
  emit('saveSportProfile')
}

function formatTeacherPrice(cents) {
  if (cents === null || cents === undefined) return 'Valor a combinar'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}


watch(() => props.sportProfileSaveSuccess, (saved) => {
  if (saved) {
    isEditing.value = false
    practicesEditorOpen.value = false
    availabilityEditorOpen.value = false
  }
})

async function highlightBioContext() {
  bioContextInvalid.value = true
  await nextTick()
  sportPracticesField.value?.scrollIntoView?.({ behavior: 'smooth', block: 'center' })
}

</script>

<template>
  <div class="participant-stage">
    <main class="participant-shell" :aria-label="`Modo Participante · ${activeTab.label}`">
      <header class="participant-topbar">
        <div class="profile-chip" aria-label="Perfil Esportivo ativo">
          <span class="profile-avatar">{{ initials }}</span>
          <span class="profile-copy">
            <strong>{{ sportProfile.displayName }}</strong>
            <small>{{ sportProfile.role }} · {{ sportProfile.primaryModality }}</small>
          </span>
        </div>
        <button class="participant-logout" type="button" aria-label="Sair da conta" title="Sair" @click="emit('logout')">
          <Icon name="logout" :size="18" />
        </button>

      </header>

      <section class="participant-content" :aria-labelledby="`${activeTab.id}-title`">
        <p class="participant-eyebrow">{{ activeTab.eyebrow }}</p>
        <div class="participant-title-row">
          <h1 :id="`${activeTab.id}-title`">{{ activeTab.title }}</h1>
          <button
            v-if="isDiscoverTab"
            type="button"
            :class="['discovery-filter-toggle', { active: discoveryFiltersOpen || hasDiscoveryFilters }]"
            :aria-expanded="discoveryFiltersOpen"
            aria-controls="discovery-filters"
            @click="discoveryFiltersOpen = true"
          >
            <Icon name="filter" :size="16" />
            <span>Filtros</span>
          </button>
        </div>
        <p v-if="isDiscoverTab && activeFilterSummary" class="discovery-filter-summary">{{ activeFilterSummary }}</p>

        <div v-if="isDiscoverTab && discoverySkeletonVisible && !primaryDiscoveryCard && !discoveryError" class="discovery-deck discovery-deck-loading" aria-busy="true" aria-label="Descoberta carregando">
          <div class="session-card session-card-skeleton skeleton-surface" aria-hidden="true">
            <div class="skeleton-card-header"><Skeleton variant="badge" width="94px" height="26px" /><Skeleton variant="text" width="48px" /></div>
            <div class="skeleton-card-main"><Skeleton variant="text" width="34%" /><Skeleton variant="title" width="78%" height="32px" /><Skeleton variant="text" width="58%" /></div>
            <div class="skeleton-fact-grid"><div v-for="item in 4" :key="item"><Skeleton variant="text" width="45%" height="10px" /><Skeleton variant="text" width="78%" height="16px" /></div></div>
            <Skeleton variant="text" :lines="2" height="13px" />
            <Skeleton variant="button" width="110px" height="38px" />
            <div class="skeleton-action-buttons"><Skeleton variant="button" height="44px" /><Skeleton variant="button" height="44px" /><Skeleton variant="button" height="44px" /></div>
          </div>
        </div>

        <div v-else-if="isDiscoverTab && discoveryLoading && !primaryDiscoveryCard && !discoveryError" class="loading-grace" aria-busy="true"></div>

        <div
          v-else-if="isDiscoverTab && primaryDiscoveryCard"
          class="discovery-deck"
          aria-label="Deck Descobrir"
        >
          <div class="deck-shadow deck-shadow-back" aria-hidden="true"></div>
          <div class="deck-shadow deck-shadow-mid" aria-hidden="true"></div>

          <article
            :class="['session-card', 'discovery-action-card', { 'is-dragging': discoveryDragging }]"
            :aria-label="primaryDiscoveryCard.accessibilityLabel"
            :style="{ transform: `translateX(${discoveryDragX}px) rotate(${discoveryDragX / 24}deg)` }"
            @pointerdown="beginDiscoveryPointer"
            @pointermove="moveDiscoveryPointer"
            @pointerup="endDiscoveryPointer"
            @pointercancel="endDiscoveryPointer"
          >
            <div :class="['discovery-card-hero', primaryDiscoveryCard.entryBadge.kind === 'curated' ? 'is-curated' : 'is-open']">
              <Icon class="discovery-card-sport-icon" :name="primaryDiscoveryCard.modalityIcon" :size="64" />
              <span :class="['session-entry-badge', primaryDiscoveryCard.entryBadge.toneClass]">
                <Icon :name="primaryDiscoveryCard.entryBadge.icon" :size="14" />
                <span>{{ primaryDiscoveryCard.entryBadge.label }}</span>
              </span>
              <span v-if="primaryDiscoveryCard.distanceLabel" class="session-distance">
                <Icon name="map" :size="13" />{{ primaryDiscoveryCard.distanceLabel }}
              </span>
              <span class="discovery-swipe-stamp discovery-swipe-stamp-skip" :style="{ opacity: Math.min(1, Math.max(0, -discoveryDragX / 70)) }">PULAR</span>
              <span class="discovery-swipe-stamp discovery-swipe-stamp-join" :style="{ opacity: Math.min(1, Math.max(0, discoveryDragX / 70)) }">VOU</span>
            </div>

            <div class="session-card-main">
              <h2>{{ primaryDiscoveryCard.title }}</h2>
              <p class="session-host">
                {{ primaryDiscoveryCard.hostRoleLabel }} · {{ primaryDiscoveryCard.hostLabel }}
              </p>
            </div>

            <dl class="session-facts">
              <div>
                <dt>Nivel Esportivo</dt>
                <dd>{{ primaryDiscoveryCard.levelLabel }}</dd>
              </div>
              <div v-if="primaryDiscoveryCard.participantCountLabel">
                <dt>Participantes</dt>
                <dd>{{ primaryDiscoveryCard.participantCountLabel }}</dd>
              </div>
              <div v-if="primaryDiscoveryCard.locationLabel">
                <dt>Local</dt>
                <dd>{{ primaryDiscoveryCard.locationLabel }}</dd>
              </div>
            </dl>

            <p v-if="primaryDiscoveryCard.recommendationReason" class="session-reason">{{ primaryDiscoveryCard.recommendationReason }}</p>

            <button
              class="session-detail-trigger"
              type="button"
              @click="emit('selectDiscoveryCard', primaryRawDiscoveryCard)"
            >
              Ver detalhes
            </button>
            <div class="discovery-action-buttons" aria-label="Acoes da Descoberta">
              <button type="button" class="discovery-action-button discovery-action-back" :disabled="!discoveryCanUndo || discoveryActionLoading" @click="emit('undoDiscoveryAction')">
                <Icon name="back" :size="16" /><span>Voltar</span>
              </button>
              <button type="button" class="discovery-action-button discovery-action-skip" :disabled="discoveryActionLoading" @click="emit('skipDiscoverySession')">
                <Icon name="x" :size="16" /><span>Pular</span>
              </button>
              <button type="button" class="discovery-action-button discovery-action-interest" :disabled="discoveryActionLoading || !primaryDiscoveryCard.canShowInterest" @click="emit('showInterestInDiscoverySession')">
                <Icon :name="primaryDiscoveryCard.primaryActionIcon" :size="16" /><span>{{ discoveryActionLoading ? 'Enviando' : primaryDiscoveryCard.primaryActionLabel }}</span>
              </button>
            </div>
            <p v-if="discoveryActionFeedback" class="discovery-action-feedback" aria-live="polite">{{ discoveryActionFeedback }}</p>
            <p v-if="discoveryActionError" class="discovery-action-feedback discovery-action-feedback-error" role="alert">{{ discoveryActionError }}</p>
          </article>
        </div>

        <section v-else-if="isMapTab && nearbySkeletonVisible && !nearbySessionViews.length && !nearbySessionsError" class="nearby-stage nearby-stage-skeleton" aria-busy="true" aria-label="Sessoes proximas carregando">
          <div class="nearby-surface-toggle skeleton-surface" aria-hidden="true"><Skeleton variant="button" height="36px" /><Skeleton variant="button" height="36px" /></div>
          <div class="nearby-map nearby-map-skeleton skeleton-surface" aria-hidden="true">
            <Skeleton class="skeleton-map-pin skeleton-map-pin-one" variant="card" width="92px" height="58px" radius="16px" />
            <Skeleton class="skeleton-map-pin skeleton-map-pin-two" variant="card" width="92px" height="58px" radius="16px" />
            <Skeleton class="skeleton-map-pin skeleton-map-pin-three" variant="card" width="92px" height="58px" radius="16px" />
          </div>
        </section>

        <div v-else-if="isMapTab && nearbySessionsLoading && !nearbySessionViews.length && !nearbySessionsError" class="loading-grace" aria-busy="true"></div>

        <section v-else-if="isMapTab && !nearbySessionsError" class="nearby-stage" aria-label="Mapa e Lista de Sessoes proximas">
          <div class="nearby-surface-toggle" role="tablist" aria-label="Alternar entre Mapa e Lista">
            <button
              type="button"
              :class="['nearby-surface-button', { active: nearbySurface === 'map' }]"
              :aria-selected="nearbySurface === 'map'"
              @click="nearbySurface = 'map'"
            >
              Mapa
            </button>
            <button
              type="button"
              :class="['nearby-surface-button', { active: nearbySurface === 'list' }]"
              :aria-selected="nearbySurface === 'list'"
              @click="nearbySurface = 'list'"
            >
              Lista
            </button>
          </div>

          <template v-if="nearbySurface === 'map'">
            <NearbySessionsMap
              :sessions="nearbySessionViews"
              :selected-session-id="selectedNearbySessionId"
              :participant-avatar-url="sportProfile.avatarUrl"
              :participant-initials="initials"
              @select="selectNearbySession"
            />

            <section
              v-if="!nearbySessionViews.length"
              class="nearby-map-empty"
              aria-label="Nenhuma Sessao Esportiva nesta regiao"
            >
              <p class="nearby-map-empty-eyebrow">Sua região</p>
              <h2>Nenhuma Sessão Esportiva por aqui ainda</h2>
              <p>Quando um Organizador publicar uma Sessão Esportiva próxima, ela aparecerá neste mapa.</p>
            </section>
          </template>

          <section v-else-if="nearbySessionViews.length" class="nearby-list" aria-label="Lista de Sessoes proximas">
            <button
              v-for="session in nearbySessionViews"
              :key="`list-${session.id}`"
              type="button"
              class="nearby-list-item"
              :aria-label="session.listAriaLabel"
              @click="selectNearbySession(session.id)"
            >
              <div class="nearby-list-item-main">
                <span :class="['session-entry-badge', session.entryBadge.toneClass]">
                  <Icon :name="session.entryBadge.icon" :size="14" />
                  <span>{{ session.entryBadge.label }}</span>
                </span>
                <strong>{{ session.title }}</strong>
                <span class="nearby-list-modality"><Icon :name="session.modalityIcon" :size="14" /> {{ session.modalityLabel }} · {{ session.hostRoleLabel }} · {{ session.hostLabel }}</span>
              </div>
              <div class="nearby-list-item-meta">
                <span>{{ session.timeCueLabel }}</span>
                <span v-if="session.distanceLabel">{{ session.distanceLabel }}</span>
              </div>
            </button>
          </section>

          <section v-else class="nearby-list-empty" aria-label="Nenhuma Sessao Esportiva proxima">
            <Icon name="map" :size="24" />
            <p>Nenhuma Sessão Esportiva próxima.</p>
            <small>Use o mapa para explorar a região.</small>
          </section>

          <section
            v-if="selectedNearbySessionView"
            class="nearby-summary-sheet"
            aria-label="Resumo da Sessao Esportiva"
          >
            <button
              class="nearby-summary-close"
              type="button"
              aria-label="Fechar resumo da Sessao Esportiva"
              @click="closeNearbySessionSummary"
            >
              <Icon name="x" :size="16" />
            </button>

            <span :class="['session-entry-badge', selectedNearbySessionView.entryBadge.toneClass]">
              <Icon :name="selectedNearbySessionView.entryBadge.icon" :size="14" />
              <span>{{ selectedNearbySessionView.entryBadge.label }}</span>
            </span>

            <div class="nearby-summary-main">
              <p class="session-modality">
                <Icon :name="selectedNearbySessionView.modalityIcon" :size="15" />
                <span>{{ selectedNearbySessionView.modalityLabel }}</span>
              </p>
              <h2>{{ selectedNearbySessionView.title }}</h2>
              <p class="session-host">
                {{ selectedNearbySessionView.hostRoleLabel }} · {{ selectedNearbySessionView.hostLabel }}
              </p>
            </div>

            <dl class="nearby-summary-facts">
              <div>
                <dt>Data</dt>
                <dd>{{ selectedNearbySessionView.dateTimeLabel }}</dd>
              </div>
              <div v-if="selectedNearbySessionView.distanceLabel">
                <dt>Distancia</dt>
                <dd>{{ selectedNearbySessionView.distanceLabel }}</dd>
              </div>
              <div>
                <dt>Local</dt>
                <dd>{{ selectedNearbySessionView.locationLabel }}</dd>
              </div>
              <div v-if="selectedNearbySessionView.participantCountLabel">
                <dt>Participantes</dt>
                <dd>{{ selectedNearbySessionView.participantCountLabel }}</dd>
              </div>
            </dl>

            <p
              v-if="nearbySessionParticipationFeedback"
              :class="[
                'session-detail-feedback',
                nearbySessionParticipationFeedbackTone ? `session-detail-feedback-${nearbySessionParticipationFeedbackTone}` : '',
              ]"
            >
              {{ nearbySessionParticipationFeedback }}
            </p>

            <div class="nearby-summary-actions">
              <button
                :class="['nearby-summary-primary', selectedNearbySessionView.summaryAction.toneClass]"
                type="button"
                :disabled="nearbySessionParticipationLoading || selectedNearbySessionView.summaryAction.disabled"
                @click="emit('submitNearbySessionParticipation', selectedNearbySessionView.rawCard)"
              >
                <Icon :name="selectedNearbySessionView.summaryAction.icon" :size="16" />
                <span>{{ nearbySessionParticipationLoading ? 'Enviando' : selectedNearbySessionView.summaryAction.label }}</span>
              </button>

              <button
                class="nearby-summary-secondary"
                type="button"
                @click="emit('selectDiscoveryCard', selectedNearbySessionView.rawCard)"
              >
                Ver detalhes
              </button>
            </div>
          </section>
        </section>

        <section v-else-if="isDiscoverTab && !discoveryError" class="discovery-empty-state" aria-label="Nenhuma Sessao Esportiva para descobrir">
          <div class="discovery-empty-map" aria-hidden="true">
            <img src="/assets/session-map-preview.png" alt="" />
            <span class="discovery-empty-radius"></span>
            <span class="discovery-empty-pin"><Icon name="map" :size="26" /></span>
          </div>
          <div class="discovery-empty-copy">
            <h2>Não encontramos nenhuma Sessão Esportiva ainda</h2>
            <p>Novas Sessões Esportivas aparecerão aqui quando estiverem disponíveis.</p>
          </div>
        </section>

        <section v-else-if="isMatchesTab" class="participant-matches" aria-label="Agenda do Perfil Esportivo">
          <div class="agenda-toolbar">
            <p>{{ upcomingConfirmedMatches.length }} confirmados futuros</p>
          </div>

          <div class="match-filter-chips" role="group" aria-label="Filtrar historico de participacao">
            <button
              v-for="filter in participantMatchFilters"
              :key="filter.id"
              type="button"
              :class="['match-filter-option', { active: filter.id === participantMatchFilter }]"
              :aria-pressed="filter.id === participantMatchFilter"
              @click="emit('setParticipantMatchFilter', filter.id)"
            >
              <Icon :name="filter.id === participantMatchFilter ? 'check' : 'chevron'" :size="14" />
              {{ filter.label }}
            </button>
          </div>

          <div v-if="matchesSkeletonVisible && !participantMatchViews.length && !participantMatchesError" class="agenda-stack agenda-skeleton" aria-busy="true" aria-label="Agenda carregando">
            <div class="agenda-rail skeleton-surface" aria-hidden="true"><Skeleton v-for="item in 3" :key="item" variant="card" width="132px" height="112px" radius="14px" /></div>
            <article v-for="item in 3" :key="item" class="match-item match-item-confirmed skeleton-surface" aria-hidden="true"><Skeleton variant="card" width="68px" height="68px" radius="14px" /><div class="skeleton-match-copy"><div><Skeleton variant="text" width="72px" height="12px" /><Skeleton variant="badge" width="70px" height="24px" /></div><Skeleton variant="title" width="72%" height="20px" /><Skeleton variant="text" width="44%" /><Skeleton variant="text" width="62%" /><Skeleton variant="button" width="100px" height="38px" /></div></article>
          </div>
          <div v-else-if="participantMatchesLoading && !participantMatchViews.length && !participantMatchesError" class="loading-grace" aria-busy="true"></div>
          <div v-else-if="participantMatchesError" class="participant-placeholder">
            <div class="placeholder-icon"><Icon name="bolt" :size="28" /></div>
            <div><h2>Agenda indisponivel</h2><p>{{ participantMatchesError }}</p><button class="participant-placeholder-action" type="button" @click="emit('retryParticipantMatches')">Tentar novamente</button></div>
          </div>
          <div v-else-if="participantMatchViews.length" class="agenda-stack">
            <section v-if="agendaPreviewMatches.length" class="agenda-rail" aria-label="Resumo dos proximos eventos">
              <button
                v-for="match in agendaPreviewMatches"
                :key="`agenda-${match.id}`"
                type="button"
                class="agenda-chip"
                @click="emit('selectParticipantMatch', match.session)"
              >
                <span class="agenda-chip-icon"><Icon :name="match.modalityIcon" :size="18" /></span>
                <strong>{{ match.modality }}</strong>
                <span>{{ match.dayLabel }} - {{ match.timeLabel }}</span>
              </button>
            </section>

            <div v-if="upcomingConfirmedMatches.length" class="match-list">
              <article v-for="match in upcomingConfirmedMatches" :key="match.id" class="match-item match-item-confirmed">
                <div class="match-date-block"><strong>{{ match.dayLabel }}</strong><span>{{ match.timeLabel }}</span></div>
                <div class="match-item-main">
                  <div class="match-item-heading"><span class="match-modality"><Icon :name="match.modalityIcon" :size="14" /> {{ match.modality }}</span><span :class="['match-status', match.status.toneClass]"><Icon :name="match.status.icon" :size="14" /><span>{{ match.status.label }}</span></span></div>
                  <h2>{{ match.title }}</h2>
                  <p class="match-host">{{ match.host }}</p>
                  <p class="match-location"><Icon name="map" :size="14" /> {{ match.location }}</p>
                  <button v-if="match.canOpen" class="session-detail-trigger" type="button" @click="emit('selectParticipantMatch', match.session)">Ver detalhes</button>
                </div>
              </article>
            </div>

            <section v-else class="participant-placeholder agenda-empty">
              <div class="placeholder-icon"><Icon name="calendarCheck" :size="28" /></div>
              <div><h2>Nenhum evento confirmado</h2><p>Pedidos aguardando aprovacao e eventos encerrados ficam no historico.</p></div>
            </section>

            <details v-if="historyMatches.length" class="match-history">
              <summary>Historico e solicitacoes <span>{{ filteredHistoryMatches.length }}</span></summary>
              <article v-for="match in filteredHistoryMatches" :key="`history-${match.id}`" class="match-history-item">
                <div>
                  <span :class="['match-status', match.status.toneClass]"><Icon :name="match.status.icon" :size="14" /><span>{{ match.status.label }}</span></span>
                  <h3>{{ match.title }}</h3>
                  <p class="match-history-modality">{{ match.dateTime }} - <Icon :name="match.modalityIcon" :size="14" /> {{ match.modality }}</p>
                  <p v-if="match.pendingNotice" class="match-pending-notice">{{ match.pendingNotice }}</p>
                </div>
                <button v-if="match.canOpen" class="match-history-action" type="button" @click="emit('selectParticipantMatch', match.session)">Detalhes</button>
              </article>
              <p v-if="!filteredHistoryMatches.length" class="match-history-empty">Nenhum item neste filtro.</p>
            </details>
          </div>
          <div v-else class="participant-placeholder"><div class="placeholder-icon"><Icon name="calendarCheck" :size="28" /></div><div><h2>Nenhuma participacao ainda</h2><p>Explore eventos e confirme presenca para montar sua agenda.</p></div></div>
        </section>

        <section v-else-if="isProfileTab" class="sport-profile-editor" :aria-label="isEditing ? 'Editar Perfil Esportivo' : 'Perfil Esportivo'">
          <div class="profile-editor-intro">
            <span class="profile-editor-icon"><Icon name="user" :size="22" /></span>
            <div><h2>Perfil Esportivo ativo</h2><p>Estas preferencias orientam a Descoberta. Elas pertencem ao Perfil Esportivo, nao ao User de autenticacao.</p></div>
            <button v-if="!isEditing" type="button" class="profile-edit-button" @click="startEditing()"><Icon name="edit" :size="15" /> Editar</button>
          </div>

          <template v-if="!isEditing">
            <section class="profile-details-card" aria-label="Dados pessoais">
              <div class="profile-section-heading"><h3>Dados pessoais</h3><button type="button" class="profile-section-action" @click="startEditing()"><Icon name="edit" :size="14" /> Editar</button></div>
              <strong class="profile-display-name">{{ sportProfileDraft?.profile?.display_name || 'Defina o nome do seu Perfil Esportivo' }}</strong>
              <p v-if="sportProfileDraft?.profile?.bio" class="profile-bio-copy">{{ sportProfileDraft.profile.bio }}</p>
              <p v-else class="profile-empty-copy">Conte um pouco sobre suas praticas e objetivos.</p>
              <div class="profile-location-row"><Icon name="map" :size="16" /><span v-if="locationLabel">{{ locationLabel }}</span><button v-else type="button" class="profile-link-action" @click="startEditing()">Adicionar localizacao</button></div>
            </section>

            <section class="profile-details-card" aria-label="Modalidades">
              <div class="profile-section-heading"><h3>Modalidades</h3><button type="button" class="profile-section-action" @click="startEditing('practices')"><Icon name="edit" :size="14" /> Editar</button></div>
              <div v-if="sportProfileDraft?.sports?.length" class="profile-practice-summary">
                <div v-for="practice in sportProfileDraft.sports" :key="practice.sport_id || practice.name">
                  <strong>{{ practice.name }}</strong><span>{{ [practice.level, practice.goals?.join(', ')].filter(Boolean).join(' · ') || 'Detalhes a definir' }}</span>
                </div>
              </div>
              <button v-else type="button" class="profile-add-button" @click="startEditing('practices')"><Icon name="plus" :size="15" /> Adicionar modalidade</button>
            </section>

            <section class="profile-details-card" aria-label="Disponibilidade">
              <div class="profile-section-heading"><h3>Disponibilidade</h3><button v-if="sportProfileDraft?.availability?.length" type="button" class="profile-section-action" @click="startEditing('availability')"><Icon name="edit" :size="14" /> Editar</button></div>
              <ul v-if="sportProfileDraft?.availability?.length" class="profile-availability-summary"><li v-for="(window, index) in sportProfileDraft.availability" :key="index">{{ availabilityLabel(window) }}</li></ul>
              <button v-else type="button" class="profile-add-button" @click="startEditing('availability')"><Icon name="plus" :size="15" /> Adicionar horario</button>
            </section>

            <section v-if="isTeacher" class="profile-details-card" aria-label="Configurações de Professor">
              <div class="profile-section-heading"><h3>Configurações de Professor</h3><button type="button" class="profile-section-action" @click="startEditing()"><Icon name="edit" :size="14" /> Editar</button></div>
              <strong>{{ teacherProfileDraft.headline || 'Apresente a sua especialidade' }}</strong>
              <p v-if="teacherProfileDraft.credentials" class="profile-bio-copy">{{ teacherProfileDraft.credentials }}</p>
              <div class="teacher-settings-summary"><span>{{ formatTeacherPrice(teacherProfileDraft.hourly_price_cents) }}</span><span>{{ teacherProfileDraft.service_radius_km ? `${teacherProfileDraft.service_radius_km} km de atendimento` : 'Raio de atendimento a definir' }}</span></div>
            </section>
          </template>

          <template v-else>
            <ProfileBioAssistant
              v-if="sportProfileDraft"
              :draft="sportProfileDraft"
              :profile="sportProfile"
              @missing-context="highlightBioContext"
              @accepted="emit('acceptBioSuggestion', $event)"
              @edit="emit('applyBioSuggestion', $event)"
            />
            <form v-if="sportProfileDraft" class="profile-form" @submit.prevent="saveProfile">
              <section class="profile-edit-card">
                <label><span>Nome do Perfil Esportivo</span><input v-model="sportProfileDraft.profile.display_name" required maxlength="80"></label>
                <label><span>Bio</span><textarea v-model="sportProfileDraft.profile.bio" maxlength="1000" rows="3"></textarea></label>
                <div class="profile-location-editor"><span>Localizacao</span><button type="button" class="profile-location-button" @click="locationEditorOpen = true"><Icon name="map" :size="16" /> {{ locationLabel || 'Definir local' }}<Icon name="edit" :size="14" /></button></div>
              </section>

              <section ref="sportPracticesField" :class="['profile-practices', { 'is-invalid': bioContextInvalid }]" :aria-invalid="bioContextInvalid ? 'true' : undefined">
                <div class="profile-section-heading"><h3>Modalidades</h3><button v-if="!practicesEditorOpen" type="button" class="profile-section-action" @click="practicesEditorOpen = true"><Icon name="edit" :size="14" /> Editar</button></div>
                <template v-if="practicesEditorOpen">
                  <div v-for="practice in sportProfileDraft.sports" :key="practice.sport_id || practice.name" class="profile-practice">
                    <strong>{{ practice.name }}</strong>
                    <label><span>Nivel Esportivo</span><input v-model="practice.level"></label>
                    <label><span>Objetivos Esportivos (separados por virgula)</span><input :value="practice.goals.join(', ')" @input="updateGoals(practice, $event)"></label>
                    <label><span>Posicoes preferidas</span><input v-model="practice.preferred_positions"></label>
                  </div>
                  <p v-if="!sportProfileDraft.sports.length" class="profile-form-note">Nenhuma Modalidade cadastrada ainda.</p>
                </template>
                <div v-else-if="sportProfileDraft.sports.length" class="profile-practice-summary"><div v-for="practice in sportProfileDraft.sports" :key="practice.sport_id || practice.name"><strong>{{ practice.name }}</strong><span>{{ [practice.level, practice.goals?.join(', ')].filter(Boolean).join(' · ') || 'Detalhes a definir' }}</span></div></div>
                <button v-if="!sportProfileDraft.sports.length && !practicesEditorOpen" type="button" class="profile-add-button" @click="practicesEditorOpen = true"><Icon name="plus" :size="15" /> Adicionar modalidade</button>
              </section>

              <section :class="['profile-practices', { 'is-invalid': sportProfileSaveErrors.windows }]" :aria-invalid="sportProfileSaveErrors.windows ? 'true' : undefined">
                <div class="profile-section-heading"><h3>Disponibilidade</h3><button v-if="!availabilityEditorOpen" type="button" class="profile-section-action" @click="availabilityEditorOpen = true"><Icon name="edit" :size="14" /> Editar</button></div>
                <template v-if="availabilityEditorOpen">
                  <div v-for="(window, index) in sportProfileDraft.availability" :key="index" class="profile-availability">
                    <button type="button" class="profile-weekday-trigger" :aria-label="`Dia da semana: ${weekdayLabels[window.weekday]}`" @click="openAvailabilityDayPicker(index)">{{ weekdayLabels[window.weekday] }}</button>
                    <input v-model="window.starts_at" type="time" aria-label="Inicio"><input v-model="window.ends_at" type="time" aria-label="Fim">
                    <button type="button" class="profile-remove-button" aria-label="Remover disponibilidade" @click="removeAvailability(index)"><Icon name="x" :size="15" /></button>
                  </div>
                  <button type="button" class="profile-add-button" @click="addAvailability"><Icon name="plus" :size="15" /> Adicionar horario</button>
                </template>
                <ul v-else-if="sportProfileDraft.availability.length" class="profile-availability-summary"><li v-for="(window, index) in sportProfileDraft.availability" :key="index">{{ availabilityLabel(window) }}</li></ul>
                <button v-else type="button" class="profile-add-button" @click="addAvailability(); availabilityEditorOpen = true"><Icon name="plus" :size="15" /> Adicionar horario</button>
                <p v-if="firstValidationError(sportProfileSaveErrors, 'windows')" class="field-error">{{ firstValidationError(sportProfileSaveErrors, 'windows') }}</p>
              </section>

              <section v-if="isTeacher" class="profile-edit-card" aria-label="Configurações de Professor">
                <div class="profile-section-heading"><h3>Configurações de Professor</h3><span class="profile-teacher-badge">Professor</span></div>
                <p class="profile-form-note">Essas informações aparecem quando Entusiastas encontram o seu Perfil Esportivo.</p>
                <label><span>Especialidade</span><input :value="teacherProfileDraft.headline" maxlength="160" placeholder="Ex.: Professora de corrida para iniciantes" @input="emit('updateTeacherProfileField', 'headline', $event.target.value)"></label>
                <label><span>Credenciais e experiência</span><textarea :value="teacherProfileDraft.credentials" maxlength="2000" rows="3" placeholder="Ex.: CREF ativo, formação e experiência" @input="emit('updateTeacherProfileField', 'credentials', $event.target.value)"></textarea></label>
                <div class="profile-form-grid"><label><span>Valor por hora (R$)</span><input :value="teacherHourlyPrice" type="number" min="0" step="0.01" inputmode="decimal" placeholder="120,00" @input="emit('updateTeacherHourlyPrice', $event.target.value)"></label><label><span>Raio de atendimento (km)</span><input :value="teacherProfileDraft.service_radius_km ?? ''" type="number" min="0" max="1000" step="1" placeholder="15" @input="emit('updateTeacherProfileField', 'service_radius_km', $event.target.value)"></label></div>
              </section>

              <p class="profile-discovery-note"><Icon name="sparkles" :size="15" /> Atualizar o Perfil Esportivo atualiza os criterios usados pela Descoberta.</p>
              <p v-if="sportProfileSaveError" class="profile-feedback profile-feedback-error" role="alert">{{ sportProfileSaveError }}</p>
              <p v-if="sportProfileSaveSuccess" class="profile-feedback profile-feedback-success" role="status">Perfil Esportivo salvo. A Descoberta foi atualizada.</p>
              <button class="profile-save-button" type="submit" :disabled="sportProfileSaving"><Icon name="check" :size="17" /> {{ sportProfileSaving ? 'Salvando' : 'Salvar alteracoes' }}</button>
            </form>
          </template>
          <div class="profile-mode-affordance"><Icon name="sparkles" :size="17" /><span>{{ isTeacher ? 'Professor ativo · Suas configurações estão prontas para completar' : 'Participante agora · Anfitriao em breve' }}</span></div>
        </section>

        <div v-else class="participant-placeholder">
          <div class="placeholder-icon">
            <Icon :name="discoveryError || nearbySessionsError ? 'bolt' : activeTab.icon" :size="28" />
          </div>
          <div>
            <h2>{{ isDiscoverTab ? (discoveryError?.title || activeTab.emptyState.title) : isMapTab ? (nearbySessionsError?.title || activeTab.emptyState.title) : activeTab.emptyState.title }}</h2>
            <p>{{ isDiscoverTab ? (discoveryError?.description || activeTab.emptyState.description) : isMapTab ? (nearbySessionsError?.description || activeTab.emptyState.description) : activeTab.emptyState.description }}</p>
            <button
              v-if="isDiscoverTab && discoveryError"
              class="participant-placeholder-action"
              type="button"
              @click="emit('retryDiscovery')"
            >
              {{ discoveryError.retryLabel }}
            </button>
            <button
              v-if="isMapTab && nearbySessionsError"
              class="participant-placeholder-action"
              type="button"
              @click="emit('retryNearbySessions')"
            >
              {{ nearbySessionsError.retryLabel }}
            </button>
          </div>
        </div>

      </section>

      <section
        v-if="sportSessionDetailOpen"
        class="session-detail-panel"
        aria-label="Detalhe da Sessao Esportiva"
      >
        <header :class="['session-detail-header', sportSessionDetailView?.entryBadge?.kind === 'curated' ? 'is-curated' : 'is-open']">
          <button
            class="session-detail-close"
            type="button"
            aria-label="Fechar detalhe da Sessao Esportiva"
            @click="emit('closeSportSessionDetail')"
          >
            <Icon name="back" :size="18" />
          </button>
          <div v-if="detailSkeletonVisible" class="session-detail-loading-header" aria-hidden="true"><Skeleton variant="text" width="28%" height="12px" /><Skeleton variant="title" width="76%" height="30px" /><Skeleton variant="text" width="58%" /></div>
          <Icon v-if="sportSessionDetailView" class="session-detail-sport-icon" :name="sportSessionDetailView.modalityIcon || 'sportDefault'" :size="62" />
          <span v-if="sportSessionDetailView" :class="['session-entry-badge', sportSessionDetailView.entryBadge.toneClass]">
            <Icon :name="sportSessionDetailView.entryBadge.icon" :size="14" />
            {{ sportSessionDetailView.entryBadge.label }}
          </span>
        </header>

        <div v-if="detailSkeletonVisible" class="session-detail-loading skeleton-surface" aria-busy="true" aria-label="Detalhe carregando">
          <div class="session-detail-quick-facts" aria-hidden="true"><div v-for="item in 2" :key="item"><Skeleton variant="text" width="48%" height="10px" /><Skeleton variant="text" width="84%" height="18px" /></div></div>
          <div class="session-detail-map-preview skeleton-map-detail" aria-hidden="true"><Skeleton variant="image" width="100%" height="132px" radius="0" /><div class="skeleton-map-copy"><Skeleton variant="text" width="42%" height="10px" /><Skeleton variant="title" width="82%" height="18px" /><Skeleton variant="text" width="66%" /><Skeleton variant="button" width="92px" height="34px" /></div></div>
          <Skeleton variant="text" :lines="3" height="14px" aria-hidden="true" />
          <div class="session-detail-section" aria-hidden="true"><Skeleton variant="text" width="24%" height="10px" /><Skeleton variant="text" :lines="2" height="13px" /></div>
        </div>

        <div v-else-if="sportSessionDetailLoading" class="loading-grace session-detail-loading-grace" aria-busy="true"></div>

        <div v-else-if="sportSessionDetailError" class="participant-placeholder session-detail-error">
          <div class="placeholder-icon">
            <Icon name="bolt" :size="28" />
          </div>
          <div>
            <h2>Detalhe indisponivel</h2>
            <p>{{ sportSessionDetailError }}</p>
          </div>
        </div>

        <div v-else-if="sportSessionDetailView" class="session-detail-body">
          <div class="session-detail-title-block">
            <h2>{{ sportSessionDetailView.title }}</h2>
            <p><span class="session-host-avatar">{{ sportSessionDetailView.hostLabel?.charAt(0) }}</span> {{ sportSessionDetailView.hostRoleLabel }} · {{ sportSessionDetailView.hostLabel }}</p>
          </div>
          <div class="session-detail-status-row">
            <span :class="['session-entry-badge', sportSessionDetailView.entryBadge.toneClass]">
              <Icon :name="sportSessionDetailView.entryBadge.icon" :size="14" />
              <span>{{ sportSessionDetailView.confirmed ? 'Confirmado' : sportSessionDetailView.entryBadge.label }}</span>
            </span>
            <span class="session-detail-level">{{ sportSessionDetailView.levelLabel }}</span>
          </div>

          <dl class="session-detail-quick-facts">
            <div>
              <dt>Quando</dt>
              <dd>{{ sportSessionDetailView.dateTimeLabel }}</dd>
            </div>
            <div>
              <dt>Com quem</dt>
              <dd>{{ sportSessionDetailView.hostRoleLabel }} - {{ sportSessionDetailView.hostLabel }}</dd>
            </div>
          </dl>

          <section class="session-detail-map-preview" aria-label="Local da atividade">
            <div class="map-preview-art" aria-hidden="true">
              <img src="/assets/session-map-preview.png" alt="">
              <strong><Icon name="map" :size="18" /></strong>
            </div>
            <div class="map-preview-copy">
              <p>Local da atividade</p>
              <h3>{{ sportSessionDetailView.locationLabel }}</h3>
              <span>{{ sportSessionDetailView.meetingPoint }}</span>
            </div>
            <button class="map-preview-action" type="button">
              <Icon name="external" :size="15" />
              Navegar
            </button>
          </section>

          <p class="session-detail-summary">{{ sportSessionDetailView.description }}</p>

          <p v-if="sportSessionDetailView.participantCountLabel" class="session-detail-people">
            <Icon name="user" :size="15" />
            {{ sportSessionDetailView.participantCountLabel }} confirmados
          </p>

          <span :class="['session-entry-badge', sportSessionDetailView.entryBadge.toneClass]">
            <Icon :name="sportSessionDetailView.entryBadge.icon" :size="14" />
            <span>{{ sportSessionDetailView.confirmed ? 'Confirmado' : sportSessionDetailView.entryBadge.label }}</span>
          </span>

          <p v-if="sportSessionDetailView.approvalNotice" class="session-detail-notice">
            {{ sportSessionDetailView.approvalNotice }}
          </p>

          <p class="session-detail-description">{{ sportSessionDetailView.description }}</p>

          <dl class="session-detail-facts">
            <div>
              <dt>Anfitriao da Sessao</dt>
              <dd>{{ sportSessionDetailView.hostRoleLabel }} · {{ sportSessionDetailView.hostLabel }}</dd>
            </div>
            <div>
              <dt>Data</dt>
              <dd>{{ sportSessionDetailView.dateTimeLabel }}</dd>
            </div>
            <div>
              <dt>Nivel Esportivo</dt>
              <dd>{{ sportSessionDetailView.levelLabel }}</dd>
            </div>
            <div>
              <dt>Ponto de encontro</dt>
              <dd>{{ sportSessionDetailView.meetingPoint }}</dd>
            </div>
            <div v-if="sportSessionDetailView.participantCountLabel">
              <dt>Participantes</dt>
              <dd>{{ sportSessionDetailView.participantCountLabel }}</dd>
            </div>
          </dl>

          <section class="session-detail-section">
            <h3>Regras</h3>
            <ul>
              <li v-for="rule in sportSessionDetailView.rules" :key="rule">{{ rule }}</li>
            </ul>
          </section>

          <section class="session-detail-section">
            <h3>Equipamentos</h3>
            <ul>
              <li v-for="item in sportSessionDetailView.equipment" :key="item">{{ item }}</li>
            </ul>
          </section>

          <section v-if="sportSessionDetailView.participants.length" class="session-detail-section">
            <h3>Pessoas indo</h3>
            <p>{{ sportSessionDetailView.participants.join(', ') }}</p>
          </section>

          <p
            v-if="sportSessionDetailView.participationFeedback"
            :class="[
              'session-detail-feedback',
              sportSessionDetailView.participationFeedbackTone
                ? `session-detail-feedback-${sportSessionDetailView.participationFeedbackTone}`
                : sportSessionParticipationFeedbackTone
                  ? `session-detail-feedback-${sportSessionParticipationFeedbackTone}`
                  : '',
            ]"
            aria-live="polite"
          >
            {{ sportSessionDetailView.participationFeedback }}
          </p>
        </div>

        <footer v-if="sportSessionDetailView?.primaryActionLabel" class="session-detail-footer">
          <button
            :class="['session-detail-primary', sportSessionDetailView.primaryActionToneClass]"
            type="button"
            :disabled="sportSessionParticipationLoading || !sportSessionDetailView.canSubmitParticipation"
            @click="emit('submitSportSessionParticipation')"
          >
            <Icon :name="sportSessionDetailView.primaryActionIcon" :size="18" />
            <span>{{
              sportSessionParticipationLoading
                ? 'Enviando'
                : sportSessionParticipationConfirmed
                  ? 'Confirmado'
                  : sportSessionDetailView.primaryActionLabel
            }}</span>
          </button>
        </footer>
      </section>

      <BottomSheet
        :open="locationEditorOpen"
        title="Definir localizacao"
        @close="locationEditorOpen = false"
      >
        <label class="profile-location-sheet-field"><span>Cidade e regiao</span><input v-model="locationDraft" placeholder="Florianopolis - SC" @keydown.enter="locationEditorOpen = false"></label>
        <p class="profile-form-note">Use o formato Cidade - UF para manter sua localizacao aproximada atualizada.</p>
      </BottomSheet>

      <BottomSheet
        :open="discoveryFiltersOpen"
        title="Filtrar Descoberta"
        @close="discoveryFiltersOpen = false"
      >
        <form id="discovery-filters" class="discovery-filters" aria-label="Filtros da Descoberta" @submit.prevent="applyFilters">
          <fieldset><legend>Modalidade</legend><button v-for="option in DISCOVERY_SPORT_OPTIONS" :key="option.value" type="button" :class="['filter-choice', { active: draftFilters.sportSlug === option.value }]" @click="draftFilters.sportSlug = option.value">{{ option.label }}</button></fieldset>
          <fieldset><legend>Distancia</legend><button v-for="distance in [5, 10, 20, 50]" :key="distance" type="button" :class="['filter-choice', { active: draftFilters.distanceKm === distance }]" @click="draftFilters.distanceKm = distance">{{ distance }} km</button></fieldset>
          <fieldset><legend>Nivel Esportivo</legend><button v-for="option in DISCOVERY_LEVEL_OPTIONS" :key="option.value" type="button" :class="['filter-choice', { active: draftFilters.level === option.value }]" @click="draftFilters.level = option.value">{{ option.label }}</button></fieldset>
          <fieldset><legend>Objetivo Esportivo</legend><button v-for="option in DISCOVERY_GOAL_OPTIONS" :key="option.value" type="button" :class="['filter-choice', { active: draftFilters.goal === option.value }]" @click="draftFilters.goal = option.value">{{ option.label }}</button></fieldset>
          <fieldset><legend>Disponibilidade</legend><button v-for="option in DISCOVERY_WEEKDAY_OPTIONS" :key="option.value" type="button" :class="['filter-choice', { active: draftFilters.weekday === option.value }]" @click="draftFilters.weekday = option.value">{{ option.label }}</button></fieldset>
          <div class="filter-time-range"><label><span>Inicio</span><input v-model="draftFilters.startsAt" type="time"></label><label><span>Fim</span><input v-model="draftFilters.endsAt" type="time"></label></div>
          <fieldset><legend>Tipo</legend><button v-for="option in DISCOVERY_PARTICIPATION_TYPE_OPTIONS" :key="option.value" type="button" :class="['filter-choice', { active: draftFilters.participationType === option.value }]" @click="draftFilters.participationType = option.value">{{ option.label }}</button></fieldset>
          <div class="discovery-filter-actions"><button type="button" @click="clearFilters">Limpar</button><button type="submit">Aplicar filtros</button></div>
        </form>
      </BottomSheet>

      <BottomSheet
        :open="availabilityDayPickerIndex !== null"
        title="Escolher dia"
        @close="availabilityDayPickerIndex = null"
      >
        <div class="weekday-picker" role="group" aria-label="Dia da Disponibilidade">
          <button v-for="(label, day) in weekdayLabels" :key="day" type="button" :class="['weekday-picker-option', { active: sportProfileDraft?.availability?.[availabilityDayPickerIndex]?.weekday === day }]" @click="selectAvailabilityDay(day)">{{ label }}</button>
        </div>
      </BottomSheet>

      <nav class="participant-nav" aria-label="Navegacao do Modo Participante">
        <button
          v-for="tab in PARTICIPANT_TABS"
          :key="tab.id"
          type="button"
          :class="['participant-nav-item', { active: tab.id === store.participantTab }]"
          :aria-current="tab.id === store.participantTab ? 'page' : undefined"
          @click="store.setParticipantTab(tab.id)"
        >
          <Icon :name="tab.icon" :size="22" />
          <span>{{ tab.label }}</span>
        </button>
      </nav>
    </main>
  </div>
</template>
