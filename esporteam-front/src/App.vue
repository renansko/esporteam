<script setup>
import { watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { useAppStore } from './stores/app'
import Toast from './components/Toast.vue'
import AdultEligibilityPrompt from './components/AdultEligibilityPrompt.vue'

const store = useAppStore()
const route = useRoute()
const router = useRouter()

watch(() => store.theme, (theme) => {
  document.documentElement.setAttribute('data-theme', theme)
}, { immediate: true })

watch(() => store.auth, (authenticated) => {
  if (authenticated && route.meta.guestOnly) {
    const returnTo = typeof route.query.retorno === 'string' && route.query.retorno.startsWith('/')
      ? route.query.retorno
      : '/descobrir'
    router.replace(returnTo)
  } else if (!authenticated && route.meta.requiresAuth) {
    router.replace({ name: 'login', query: { retorno: route.fullPath } })
  }
})

</script>

<template>
  <RouterView />
  <AdultEligibilityPrompt v-if="store.auth && !store.currentUser?.is_adult" />
  <Toast />
</template>
