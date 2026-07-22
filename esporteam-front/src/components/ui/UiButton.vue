<script setup>
defineProps({
  variant: { type: String, default: 'primary' },
  busy: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
})
</script>

<template>
  <button :class="['ui-button', `ui-button--${variant}`]" :type="type" :disabled="disabled || busy" :aria-busy="busy || undefined">
    <slot />
  </button>
</template>
