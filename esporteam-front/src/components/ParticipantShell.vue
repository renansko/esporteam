<script setup>
import { computed } from 'vue'
import { useAppStore } from '../stores/app'
import { PARTICIPANT_TABS } from '../mock/sportDiscovery'
import Icon from './Icon.vue'

const store = useAppStore()

const activeTab = computed(() => (
  PARTICIPANT_TABS.find(tab => tab.id === store.participantTab) || PARTICIPANT_TABS[0]
))

const sportProfile = computed(() => store.activeSportProfile)
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

        <div class="participant-placeholder">
          <div class="placeholder-icon">
            <Icon :name="activeTab.icon" :size="28" />
          </div>
          <div>
            <h2>{{ activeTab.label }}</h2>
            <p>{{ activeTab.status }}</p>
          </div>
        </div>

        <dl class="sport-profile-summary">
          <div>
            <dt>Modalidades</dt>
            <dd>{{ sportProfile.modalities.map(item => item.name).join(', ') }}</dd>
          </div>
          <div>
            <dt>Disponibilidade</dt>
            <dd>{{ sportProfile.availability.slice(0, 2).join(', ') }}</dd>
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
