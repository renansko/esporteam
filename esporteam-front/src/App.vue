<script setup>
import { watch, onMounted, onUnmounted } from 'vue'
import { useAppStore } from './stores/app'
import Login from './components/Login.vue'
import Register from './components/Register.vue'
import WorkspaceSetup from './components/WorkspaceSetup.vue'
import Sidebar from './components/Sidebar.vue'
import Inbox from './components/Inbox.vue'
import IdeasBoard from './components/IdeasBoard.vue'
import Competitors from './components/Competitors.vue'
import PublicRoadmap from './components/PublicRoadmap.vue'
import IdeaDetail from './components/IdeaDetail.vue'
import LoopOverlay from './components/LoopOverlay.vue'
import Toast from './components/Toast.vue'

const store = useAppStore()

store.hydrateFromToken()

watch(() => store.theme, (theme) => {
  document.documentElement.setAttribute('data-theme', theme)
}, { immediate: true })

function onKey(e) {
  const tag = e.target.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA') return
  if (e.key === 'l' || e.key === 'L') {
    if (store.loopState === 'idle') store.setLoop('step1')
  }
}
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <template v-if="!store.auth">
    <Register v-if="store.authView === 'register'" />
    <Login v-else />
  </template>
  <WorkspaceSetup v-else-if="store.workspaceSetupRequired || !store.currentWorkspace" />
  <template v-else>
    <div class="app-shell" :data-screen-label="`Esporteam · ${store.page}`">
      <Sidebar />
      <div class="main">
        <Inbox         v-if="store.page === 'inbox'" />
        <IdeasBoard    v-else-if="store.page === 'ideas'" />
        <Competitors   v-else-if="store.page === 'competitors'" />
        <PublicRoadmap v-else-if="store.page === 'roadmap'" />
      </div>
    </div>
    <IdeaDetail />
    <LoopOverlay />
  </template>
  <Toast />
</template>
