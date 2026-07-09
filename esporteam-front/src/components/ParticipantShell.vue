<script setup>
import { computed } from 'vue'
import { useAppStore } from '../stores/app'
import { createSportSessionCardView } from '../features/participant/discoveryCard'
import { PARTICIPANT_TABS, resolveParticipantTab } from '../features/participant/shell'
import Icon from './Icon.vue'

const props = defineProps({
  discoveryCards: { type: Array, default: null },
})

const store = useAppStore()

const activeTab = computed(() => resolveParticipantTab(store.participantTab))
const isDiscoverTab = computed(() => activeTab.value.id === 'discover')
const primaryDiscoveryCard = computed(() => (
  props.discoveryCards?.[0]
    ? createSportSessionCardView(props.discoveryCards[0])
    : null
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
        <h1 :id="`${activeTab.id}-title`">{{ activeTab.title }}</h1>

        <div v-if="isDiscoverTab && primaryDiscoveryCard" class="discovery-deck" aria-label="Deck Descobrir">
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
          </article>
        </div>

        <div v-else class="participant-placeholder">
          <div class="placeholder-icon">
            <Icon :name="activeTab.icon" :size="28" />
          </div>
          <div>
            <h2>{{ activeTab.emptyState.title }}</h2>
            <p>{{ activeTab.emptyState.description }}</p>
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
