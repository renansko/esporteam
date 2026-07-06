<script setup>
import { computed } from 'vue'
import { useAppStore } from '../stores/app'
import { pickLang } from '../mock/i18n'

const store = useAppStore()
const text = computed(() => store.toast ? pickLang(store.toast, store.lang) : '')
</script>

<template>
  <div :class="['toast', { open: !!store.toast }]">{{ text }}</div>
</template>
