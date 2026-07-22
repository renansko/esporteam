<script setup>
defineProps({ modelValue: { type: Boolean, required: true }, label: { type: String, required: true } })
defineEmits(['update:modelValue'])
</script>

<template>
  <button class="ui-toggle" type="button" role="switch" :aria-checked="modelValue" @click="$emit('update:modelValue', !modelValue)">
    <span>{{ label }}</span><i aria-hidden="true"></i>
  </button>
</template>
