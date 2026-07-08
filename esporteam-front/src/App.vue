<script setup>
import { watch } from 'vue'
import { useAppStore } from './stores/app'
import Login from './components/Login.vue'
import Register from './components/Register.vue'
import ParticipantShell from './components/ParticipantShell.vue'
import Toast from './components/Toast.vue'

const store = useAppStore()

store.hydrateFromToken()

watch(() => store.theme, (theme) => {
  document.documentElement.setAttribute('data-theme', theme)
}, { immediate: true })

</script>

<template>
  <template v-if="!store.auth">
    <Register v-if="store.authView === 'register'" />
    <Login v-else />
  </template>
  <ParticipantShell v-else />
  <Toast />
</template>
