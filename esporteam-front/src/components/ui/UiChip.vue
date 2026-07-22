<script setup>
defineProps({ selected: { type: Boolean, default: false } })
</script>

<template>
  <button type="button" :class="['filter-choice', { active: selected }]" :aria-pressed="selected"><slot /></button>
</template>
