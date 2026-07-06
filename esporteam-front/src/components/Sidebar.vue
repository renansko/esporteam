<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'
import { SEED_WORKSPACE } from '../mock/data'
import Icon from './Icon.vue'
import ConfirmLogoutModal from './ConfirmLogoutModal.vue'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const workspaceName = computed(() => store.currentWorkspace?.name || SEED_WORKSPACE.name)
const workspaceTagline = computed(() => pickLang(SEED_WORKSPACE.tagline, lang.value))
const userName = computed(() => store.currentUser?.name || SEED_WORKSPACE.pmName)
const userEmail = computed(() => store.currentUser?.email || SEED_WORKSPACE.pmEmail)
const userProfile = computed(() => store.currentUser?.profile || 'user')
const userInitials = computed(() => {
  const name = userName.value || ''
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map(p => p[0]?.toUpperCase()).join('') || '?'
})

const items = computed(() => [
  { id: 'inbox',       label: t('nav_inbox'),       icon: 'inbox',       count: store.feedbacks.filter(f => !f.idea).length || null },
  { id: 'ideas',       label: t('nav_ideas'),       icon: 'ideas',       count: store.ideas.length },
  { id: 'competitors', label: t('nav_competitors'), icon: 'competitors', count: store.competitors.length },
])

const menuOpen = ref(false)
const showConfirm = ref(false)

function toggleMenu() { menuOpen.value = !menuOpen.value }
function closeMenu() { menuOpen.value = false }

function onLogoutClick() {
  menuOpen.value = false
  showConfirm.value = true
}

function onConfirmLogout() {
  showConfirm.value = false
  store.logout()
}

function onDocumentClick(e) {
  if (!menuOpen.value) return
  if (e.target.closest('.sb-user-menu') || e.target.closest('.sb-user-trigger')) return
  menuOpen.value = false
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<template>
  <aside class="sidebar">
    <div class="sb-hd">
      <div class="sb-logo">P</div>
      <div style="min-width: 0">
        <div class="sb-wsname">Esporteam</div>
        <div class="sb-wstag truncate">
          {{ workspaceName }} · {{ workspaceTagline }}
        </div>
      </div>
    </div>

    <div class="sb-section">
      <div class="sb-section-h">Workspace</div>
      <div v-for="it in items" :key="it.id"
           :class="['sb-item', { active: store.page === it.id }]"
           @click="store.setPage(it.id)">
        <span class="ico"><Icon :name="it.icon" /></span>
        <span>{{ it.label }}</span>
        <span v-if="it.count != null" class="count mono">{{ it.count }}</span>
      </div>
    </div>

    <div class="sb-section">
      <div class="sb-section-h">{{ lang === 'pt' ? 'Externo' : 'External' }}</div>
      <div :class="['sb-item', { active: store.page === 'roadmap' }]"
           @click="store.setPage('roadmap')">
        <span class="ico"><Icon name="roadmap" /></span>
        <span>{{ t('nav_roadmap') }}</span>
        <span class="count mono" style="display: inline-flex; align-items: center; gap: 4px">
          <Icon name="external" :size="11" />
        </span>
      </div>
    </div>

    <div style="flex: 1" />

    <div class="sb-section" style="padding-bottom: 8px">
      <div class="sb-section-h">{{ lang === 'pt' ? 'Atalhos' : 'Shortcuts' }}</div>
      <div class="sb-item" @click="store.setLoop('step1')">
        <span class="ico" style="color: var(--gold)"><Icon name="bolt" /></span>
        <span>{{ lang === 'pt' ? 'Demo: loop fechado' : 'Demo: closed loop' }}</span>
        <span class="count"><span class="kbd">L</span></span>
      </div>
    </div>

    <div class="sb-ft">
      <button
        class="sb-user-trigger"
        type="button"
        :aria-expanded="menuOpen"
        @click="toggleMenu"
      >
        <div class="av">{{ userInitials }}</div>
        <div style="min-width: 0; flex: 1; text-align: left">
          <div class="nm truncate">{{ userName }}</div>
          <div class="em truncate">{{ userEmail }} · {{ userProfile }}</div>
        </div>
        <Icon name="chevron" :size="12" />
      </button>

      <div v-if="menuOpen" class="sb-user-menu" role="menu">
        <button type="button" class="sb-menu-item" role="menuitem" @click="onLogoutClick">
          <span class="ico"><Icon name="external" :size="13" /></span>
          <span>{{ lang === 'pt' ? 'Sair' : 'Logout' }}</span>
        </button>
      </div>
    </div>
  </aside>

  <ConfirmLogoutModal
    v-if="showConfirm"
    @confirm="onConfirmLogout"
    @close="showConfirm = false"
  />
</template>
