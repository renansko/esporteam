<script setup>
import { computed, reactive, ref, watch } from 'vue'
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
import { createParticipantMatchView } from '../features/participant/matches'

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
  sportProfileSaveSuccess: { type: Boolean, default: false },
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
])

const store = useAppStore()
const filtersOpen = ref(false)
const draftFilters = reactive(createDefaultDiscoverySessionFilters())
const nearbySurface = ref(props.nearbySurfaceMode || 'map')
const selectedNearbySessionId = ref(props.nearbySelectedSessionId || null)

watch(() => props.discoveryFilters, (filters = {}) => {
  Object.assign(draftFilters, {
    ...createDefaultDiscoverySessionFilters(),
    ...filters,
  })
}, { immediate: true, deep: true })

watch(() => props.nearbySessions, (sessions = []) => {
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
const matchFilterLabel = computed(() => (
  props.participantMatchFilters.find(filter => filter.id === props.participantMatchFilter)?.label || 'Todos'
))
const isMapTab = computed(() => activeTab.value.id === 'map')
const primaryDiscoveryCard = computed(() => (
  props.discoveryCards?.[0]
    ? createSportSessionCardView(props.discoveryCards[0])
    : null
))
const primaryRawDiscoveryCard = computed(() => props.discoveryCards?.[0] ?? null)
const pointerStartX = ref(null)
function beginDiscoveryPointer(event) {
  pointerStartX.value = event.clientX
}
function endDiscoveryPointer(event) {
  if (pointerStartX.value === null) return
  const delta = event.clientX - pointerStartX.value
  pointerStartX.value = null
  if (Math.abs(delta) < 72) return
  if (delta < 0) emit('skipDiscoverySession')
  else emit('showInterestInDiscoverySession')
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
const modalityList = computed(() => (
  sportProfile.value?.modalities?.map(item => item.name).filter(Boolean).join(', ') || 'Modalidade a definir'
))
const availabilityList = computed(() => (
  sportProfile.value?.availability?.slice(0, 2).join(', ') || 'Disponibilidade a definir'
))
const initials = computed(() => {
  const name = sportProfile.value?.displayName || ''
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]?.toUpperCase()).join('') || 'PE'
})
const discoveryEmptyState = computed(() => {
  if (props.discoveryError) return props.discoveryError

  return {
    title: 'Nenhuma Sessao Esportiva por perto',
    description: props.hasDiscoveryFilters
      ? 'Nenhuma sessao proxima combina com estes filtros. Amplie a distancia ou remova uma Modalidade, Nivel Esportivo ou Disponibilidade.'
      : 'Ainda nao encontramos sessoes proximas compativeis com seu Perfil Esportivo. Amplie a distancia ou ajuste sua Disponibilidade.',
  }
})
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
}

function clearFilters() {
  emit('applyDiscoveryFilters', createDefaultDiscoverySessionFilters())
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

        <button class="mode-switch" type="button" aria-label="Modo Participante ativo">
          <Icon name="user" :size="16" />
          <span>Participante</span>
        </button>
      </header>

      <section class="participant-content" :aria-labelledby="`${activeTab.id}-title`">
        <p class="participant-eyebrow">{{ activeTab.eyebrow }}</p>
        <div class="participant-title-row">
          <h1 :id="`${activeTab.id}-title`">{{ activeTab.title }}</h1>
          <button
            v-if="isDiscoverTab"
            type="button"
            :class="['discovery-filter-toggle', { active: filtersOpen || hasDiscoveryFilters }]"
            :aria-expanded="filtersOpen"
            aria-controls="discovery-filters"
            @click="filtersOpen = !filtersOpen"
          >
            <Icon name="filter" :size="16" />
            <span>Filtros</span>
          </button>
        </div>

        <p v-if="isDiscoverTab && activeFilterSummary" class="discovery-filter-summary">
          {{ activeFilterSummary }}
        </p>

        <form
          v-if="isDiscoverTab && filtersOpen"
          id="discovery-filters"
          class="discovery-filters"
          aria-label="Filtros da Descoberta"
          @submit.prevent="applyFilters"
        >
          <label>
            <span>Modalidade</span>
            <select v-model="draftFilters.sportSlug">
              <option v-for="option in DISCOVERY_SPORT_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label>
            <span>Distancia</span>
            <select v-model.number="draftFilters.distanceKm">
              <option :value="5">5 km</option>
              <option :value="10">10 km</option>
              <option :value="20">20 km</option>
              <option :value="50">50 km</option>
            </select>
          </label>

          <label>
            <span>Nivel Esportivo</span>
            <select v-model="draftFilters.level">
              <option v-for="option in DISCOVERY_LEVEL_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label>
            <span>Objetivo Esportivo</span>
            <select v-model="draftFilters.goal">
              <option v-for="option in DISCOVERY_GOAL_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label>
            <span>Disponibilidade</span>
            <select v-model="draftFilters.weekday">
              <option v-for="option in DISCOVERY_WEEKDAY_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <div class="filter-time-range" aria-label="Janela de Disponibilidade">
            <label>
              <span>Inicio</span>
              <input v-model="draftFilters.startsAt" type="time">
            </label>
            <label>
              <span>Fim</span>
              <input v-model="draftFilters.endsAt" type="time">
            </label>
          </div>

          <fieldset>
            <legend>Tipo</legend>
            <label v-for="option in DISCOVERY_PARTICIPATION_TYPE_OPTIONS" :key="option.value">
              <input v-model="draftFilters.participationType" type="radio" :value="option.value">
              <span>{{ option.label }}</span>
            </label>
          </fieldset>

          <div class="discovery-filter-actions">
            <button type="button" @click="clearFilters">Limpar</button>
            <button type="submit">Aplicar</button>
          </div>
        </form>

        <div v-if="isDiscoverTab && discoveryLoading" class="discovery-deck discovery-deck-loading" aria-label="Descoberta carregando">
          <div class="session-card session-card-skeleton" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>

        <div
          v-else-if="isDiscoverTab && primaryDiscoveryCard"
          class="discovery-deck"
          aria-label="Deck Descobrir"
        >
          <div class="deck-shadow deck-shadow-back" aria-hidden="true"></div>
          <div class="deck-shadow deck-shadow-mid" aria-hidden="true"></div>

          <article
            class="session-card discovery-action-card"
            :aria-label="primaryDiscoveryCard.accessibilityLabel"
            @pointerdown="beginDiscoveryPointer"
            @pointerup="endDiscoveryPointer"
          >
            <header class="session-card-header">
              <span :class="['session-entry-badge', primaryDiscoveryCard.entryBadge.toneClass]">
                <Icon :name="primaryDiscoveryCard.entryBadge.icon" :size="14" />
                <span>{{ primaryDiscoveryCard.entryBadge.label }}</span>
              </span>
              <span v-if="primaryDiscoveryCard.distanceLabel" class="session-distance">
                {{ primaryDiscoveryCard.distanceLabel }}
              </span>
            </header>

            <div class="session-card-main">
              <p class="session-modality">
                <Icon :name="primaryDiscoveryCard.modalityIcon" :size="15" />
                <span>{{ primaryDiscoveryCard.modalityLabel }}</span>
              </p>
              <h2>{{ primaryDiscoveryCard.title }}</h2>
              <p class="session-host">
                {{ primaryDiscoveryCard.hostRoleLabel }} · {{ primaryDiscoveryCard.hostLabel }}
              </p>
            </div>

            <dl class="session-facts">
              <div>
                <dt>Data</dt>
                <dd>{{ primaryDiscoveryCard.dateTimeLabel }}</dd>
              </div>
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

            <p v-if="primaryDiscoveryCard.recommendationReason" class="session-reason">
              {{ primaryDiscoveryCard.recommendationReason }}
            </p>

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
                <Icon name="check" :size="16" /><span>{{ discoveryActionLoading ? 'Enviando' : 'Tenho interesse' }}</span>
              </button>
            </div>
            <p v-if="discoveryActionFeedback" class="discovery-action-feedback" aria-live="polite">{{ discoveryActionFeedback }}</p>
            <p v-if="discoveryActionError" class="discovery-action-feedback discovery-action-feedback-error" role="alert">{{ discoveryActionError }}</p>
          </article>
        </div>

        <div v-else-if="isMapTab && nearbySessionsLoading" class="participant-placeholder" aria-label="Sessoes proximas carregando">
          <div class="placeholder-icon">
            <Icon name="map" :size="28" />
          </div>
          <div>
            <h2>Carregando sessoes proximas</h2>
            <p>Mapa e Lista estao sincronizando as Sessoes Esportivas proximas ao seu Perfil Esportivo.</p>
          </div>
        </div>

        <section v-else-if="isMapTab && nearbySessionViews.length" class="nearby-stage" aria-label="Mapa e Lista de Sessoes proximas">
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

          <section v-if="nearbySurface === 'map'" class="nearby-map" aria-label="Mapa de Sessoes proximas">
            <div class="nearby-map-grid" aria-hidden="true"></div>

            <button
              v-for="session in nearbySessionViews"
              :key="`pin-${session.id}`"
              type="button"
              :class="['nearby-pin', { active: session.id === selectedNearbySessionId }]"
              :style="session.pinPosition"
              :aria-label="session.listAriaLabel"
              @click="selectNearbySession(session.id)"
            >
              <Icon :name="session.modalityIcon" :size="15" />
              <strong>{{ session.shortModalityLabel }}</strong>
              <span>{{ session.timeCueLabel }}</span>
            </button>
          </section>

          <section v-else class="nearby-list" aria-label="Lista de Sessoes proximas">
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

        <section v-else-if="isMatchesTab" class="participant-matches" aria-label="Agenda do Perfil Esportivo">
          <div class="agenda-toolbar">
            <p>{{ upcomingConfirmedMatches.length }} confirmados futuros</p>
            <button
              type="button"
              :class="['match-filter-button', { active: filtersOpen || participantMatchFilter !== 'all' }]"
              :aria-expanded="filtersOpen"
              aria-controls="match-filters"
              @click="filtersOpen = !filtersOpen"
            >
              <Icon name="filter" :size="15" />
              <span>{{ matchFilterLabel }}</span>
            </button>
          </div>

          <div v-if="filtersOpen" id="match-filters" class="match-filter-sheet" role="group" aria-label="Filtrar historico de participacao">
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

          <div v-if="participantMatchesLoading" class="participant-placeholder" aria-label="Agenda carregando">
            <div class="placeholder-icon"><Icon name="calendarCheck" :size="28" /></div>
            <div><h2>Carregando agenda</h2><p>Buscando seus eventos confirmados e historico de participacao.</p></div>
          </div>
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

        <section v-else-if="isProfileTab" class="sport-profile-editor" aria-label="Editar Perfil Esportivo">
          <div class="profile-editor-intro">
            <span class="profile-editor-icon"><Icon name="user" :size="22" /></span>
            <div><h2>Perfil Esportivo ativo</h2><p>Estas preferencias orientam a Descoberta. Elas pertencem ao Perfil Esportivo, nao ao User de autenticacao.</p></div>
          </div>
          <form v-if="sportProfileDraft" class="profile-form" @submit.prevent="emit('saveSportProfile')">
            <label><span>Nome do Perfil Esportivo</span><input v-model="sportProfileDraft.profile.display_name" required maxlength="80"></label>
            <label><span>Bio</span><textarea v-model="sportProfileDraft.profile.bio" maxlength="1000" rows="3"></textarea></label>
            <div class="profile-form-grid"><label><span>Cidade</span><input v-model="sportProfileDraft.profile.city"></label><label><span>Regiao</span><input v-model="sportProfileDraft.profile.region"></label></div>

            <fieldset class="profile-practices"><legend>Modalidades, Nivel Esportivo e Objetivos Esportivos</legend>
              <div v-for="practice in sportProfileDraft.sports" :key="practice.sport_id || practice.name" class="profile-practice">
                <strong>{{ practice.name }}</strong>
                <label><span>Nivel Esportivo</span><input v-model="practice.level"></label>
                <label><span>Objetivos Esportivos (separados por virgula)</span><input :value="practice.goals.join(', ')" @input="updateGoals(practice, $event)"></label>
                <label><span>Posicoes preferidas</span><input v-model="practice.preferred_positions"></label>
              </div>
              <p v-if="!sportProfileDraft.sports.length" class="profile-form-note">Nenhuma Modalidade cadastrada. Adicione suas praticas pelo backend antes de editar preferencias aqui.</p>
            </fieldset>

            <fieldset class="profile-practices"><legend>Disponibilidade</legend>
              <div v-for="(window, index) in sportProfileDraft.availability" :key="index" class="profile-availability">
                <select v-model.number="window.weekday" aria-label="Dia da semana"><option v-for="(label, day) in weekdayLabels" :key="day" :value="day">{{ label }}</option></select>
                <input v-model="window.starts_at" type="time" aria-label="Inicio"><input v-model="window.ends_at" type="time" aria-label="Fim">
                <button type="button" class="profile-remove-button" aria-label="Remover disponibilidade" @click="removeAvailability(index)"><Icon name="x" :size="15" /></button>
              </div>
              <button type="button" class="profile-add-button" @click="addAvailability"><Icon name="plus" :size="15" /> Adicionar horario</button>
            </fieldset>

            <p class="profile-discovery-note"><Icon name="sparkles" :size="15" /> Atualizar o Perfil Esportivo atualiza os criterios usados pela Descoberta.</p>
            <p v-if="sportProfileSaveError" class="profile-feedback profile-feedback-error" role="alert">{{ sportProfileSaveError }}</p>
            <p v-if="sportProfileSaveSuccess" class="profile-feedback profile-feedback-success" role="status">Perfil Esportivo salvo. A Descoberta foi atualizada.</p>
            <button class="profile-save-button" type="submit" :disabled="sportProfileSaving"><Icon name="check" :size="17" /> {{ sportProfileSaving ? 'Salvando' : 'Salvar Perfil Esportivo' }}</button>
          </form>
          <div class="profile-mode-affordance"><Icon name="sparkles" :size="17" /><span>Participante agora · Anfitriao em breve</span></div>
        </section>

        <div v-else class="participant-placeholder">
          <div class="placeholder-icon">
            <Icon :name="discoveryError || nearbySessionsError ? 'bolt' : activeTab.icon" :size="28" />
          </div>
          <div>
            <h2>{{ isDiscoverTab ? discoveryEmptyState.title : isMapTab ? (nearbySessionsError?.title || activeTab.emptyState.title) : activeTab.emptyState.title }}</h2>
            <p>{{ isDiscoverTab ? discoveryEmptyState.description : isMapTab ? (nearbySessionsError?.description || activeTab.emptyState.description) : activeTab.emptyState.description }}</p>
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

        <dl class="sport-profile-summary">
          <div>
            <dt>Modalidades</dt>
            <dd>{{ modalityList }}</dd>
          </div>
          <div>
            <dt>Disponibilidade</dt>
            <dd>{{ availabilityList }}</dd>
          </div>
        </dl>
      </section>

      <section
        v-if="sportSessionDetailOpen"
        class="session-detail-panel"
        aria-label="Detalhe da Sessao Esportiva"
      >
        <header class="session-detail-header">
          <button
            class="session-detail-close"
            type="button"
            aria-label="Fechar detalhe da Sessao Esportiva"
            @click="emit('closeSportSessionDetail')"
          >
            <Icon name="x" :size="18" />
          </button>
          <p class="participant-eyebrow session-detail-modality">
            <Icon v-if="sportSessionDetailView" :name="sportSessionDetailView.modalityIcon || 'sportDefault'" :size="15" />
            <span>{{ sportSessionDetailView?.modalityLabel || 'Sessao Esportiva' }}</span>
          </p>
          <h2>{{ sportSessionDetailView?.title || 'Carregando Sessao Esportiva' }}</h2>
          <p v-if="sportSessionDetailView" class="session-detail-hero-meta">
            {{ sportSessionDetailView.dateTimeLabel }} - {{ sportSessionDetailView.locationLabel }}
          </p>
        </header>

        <div v-if="sportSessionDetailLoading" class="session-detail-loading" aria-label="Detalhe carregando">
          <span></span>
          <span></span>
          <span></span>
        </div>

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
