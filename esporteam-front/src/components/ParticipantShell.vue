<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useAppStore } from '../stores/app'
import { createSportSessionCardView } from '../features/participant/discoveryCard'
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

const props = defineProps({
  discoveryCards: { type: Array, default: null },
  discoveryLoading: { type: Boolean, default: false },
  discoveryError: { type: Object, default: null },
  discoveryFilters: { type: Object, default: () => ({}) },
  hasDiscoveryFilters: { type: Boolean, default: false },
  sportSessionDetailView: { type: Object, default: null },
  sportSessionDetailOpen: { type: Boolean, default: false },
  sportSessionDetailLoading: { type: Boolean, default: false },
  sportSessionDetailError: { type: String, default: null },
  sportSessionParticipationLoading: { type: Boolean, default: false },
  sportSessionParticipationConfirmed: { type: Boolean, default: false },
})
const emit = defineEmits([
  'applyDiscoveryFilters',
  'retryDiscovery',
  'selectDiscoveryCard',
  'closeSportSessionDetail',
  'joinOpenSportSession',
])

const store = useAppStore()
const filtersOpen = ref(false)
const draftFilters = reactive(createDefaultDiscoverySessionFilters())

watch(() => props.discoveryFilters, (filters = {}) => {
  Object.assign(draftFilters, {
    ...createDefaultDiscoverySessionFilters(),
    ...filters,
  })
}, { immediate: true, deep: true })

const activeTab = computed(() => resolveParticipantTab(store.participantTab))
const isDiscoverTab = computed(() => activeTab.value.id === 'discover')
const primaryDiscoveryCard = computed(() => (
  props.discoveryCards?.[0]
    ? createSportSessionCardView(props.discoveryCards[0])
    : null
))
const primaryRawDiscoveryCard = computed(() => props.discoveryCards?.[0] ?? null)

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
            class="session-card"
            :aria-label="primaryDiscoveryCard.accessibilityLabel"
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
              <p class="session-modality">{{ primaryDiscoveryCard.modalityLabel }}</p>
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
          </article>
        </div>

        <div v-else class="participant-placeholder">
          <div class="placeholder-icon">
            <Icon :name="discoveryError ? 'bolt' : activeTab.icon" :size="28" />
          </div>
          <div>
            <h2>{{ isDiscoverTab ? discoveryEmptyState.title : activeTab.emptyState.title }}</h2>
            <p>{{ isDiscoverTab ? discoveryEmptyState.description : activeTab.emptyState.description }}</p>
            <button
              v-if="isDiscoverTab && discoveryError"
              class="participant-placeholder-action"
              type="button"
              @click="emit('retryDiscovery')"
            >
              {{ discoveryError.retryLabel }}
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
          <p class="participant-eyebrow">Sessao Esportiva</p>
          <h2>{{ sportSessionDetailView?.title || 'Carregando Sessao Esportiva' }}</h2>
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
          <span :class="['session-entry-badge', sportSessionDetailView.entryBadge.toneClass]">
            <Icon :name="sportSessionDetailView.entryBadge.icon" :size="14" />
            <span>{{ sportSessionDetailView.confirmed ? 'Confirmado' : sportSessionDetailView.entryBadge.label }}</span>
          </span>

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
            class="session-detail-feedback"
            aria-live="polite"
          >
            {{ sportSessionDetailView.participationFeedback }}
          </p>
        </div>

        <footer v-if="sportSessionDetailView?.canJoinOpen" class="session-detail-footer">
          <button
            class="session-detail-primary"
            type="button"
            :disabled="sportSessionParticipationLoading || sportSessionParticipationConfirmed"
            @click="emit('joinOpenSportSession')"
          >
            <Icon name="check" :size="18" />
            <span>{{ sportSessionParticipationConfirmed ? 'Confirmado' : sportSessionParticipationLoading ? 'Confirmando' : 'Vou participar' }}</span>
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
