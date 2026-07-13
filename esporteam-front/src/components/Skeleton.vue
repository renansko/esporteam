<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'text' },
  width: { type: [String, Number], default: '100%' },
  height: { type: [String, Number], default: null },
  radius: { type: [String, Number], default: null },
  lines: { type: Number, default: 1 },
})

const cssSize = (value) => (typeof value === 'number' ? `${value}px` : value)
const lineCount = computed(() => Math.max(1, props.lines))
const styleFor = (index) => ({
  width: index === lineCount.value - 1 && lineCount.value > 1 ? '72%' : cssSize(props.width),
  height: props.height ? cssSize(props.height) : undefined,
  borderRadius: props.radius ? cssSize(props.radius) : undefined,
})
</script>

<template>
  <span class="skeleton" :class="`skeleton-${variant}`" aria-hidden="true">
    <span v-for="index in lineCount" :key="index" class="skeleton-block" :style="styleFor(index - 1)"></span>
  </span>
</template>
