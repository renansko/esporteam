<script setup>
defineProps({
  modelValue: { type: [String, Number], required: true },
  options: { type: Array, required: true },
  label: { type: String, required: true },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <div class="ui-segmented glass-surface-thin" role="tablist" :aria-label="label">
    <button
      v-for="option in options"
      :key="option.id"
      type="button"
      role="tab"
      :class="['ui-segmented__option', { active: option.id === modelValue }]"
      :aria-selected="option.id === modelValue"
      @click="$emit('update:modelValue', option.id)"
    >
      {{ option.label }}
    </button>
  </div>
</template>
