<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
})
const emit = defineEmits(['close'])

function close() {
  emit('close')
}

function handleKeydown(event) {
  if (event.key === 'Escape' && props.open) close()
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  document.body.classList.toggle('bottom-sheet-open', props.open)
})
onBeforeUnmount(() => window.removeEventListener('keydown', handleKeydown))

watch(() => props.open, (open) => {
  document.body.classList.toggle('bottom-sheet-open', open)
})

onBeforeUnmount(() => document.body.classList.remove('bottom-sheet-open'))
</script>

<template>
  <div v-if="open" class="bottom-sheet-layer">
    <button class="bottom-sheet-backdrop" type="button" aria-label="Fechar" @click="close" />
    <section class="bottom-sheet" role="dialog" aria-modal="true" :aria-label="title">
      <div class="bottom-sheet-handle" aria-hidden="true"></div>
      <header class="bottom-sheet-header">
        <h2>{{ title }}</h2>
        <button class="bottom-sheet-close" type="button" aria-label="Fechar" @click="close">
          <Icon name="x" :size="18" />
        </button>
      </header>
      <div class="bottom-sheet-content">
        <slot />
      </div>
    </section>
  </div>
</template>
