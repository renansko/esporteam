<script setup>
const props = defineProps({ modelValue: { type: [Number, String], required: true }, min: { type: Number, default: 0 }, max: { type: Number, default: 100 }, label: { type: String, required: true } })
const emit = defineEmits(['update:modelValue'])
const change = delta => {
  const current = props.modelValue === '' ? props.min - Math.max(delta, 0) : Number(props.modelValue)
  emit('update:modelValue', Math.min(props.max, Math.max(props.min, current + delta)))
}
</script>

<template>
  <div class="ui-stepper" role="group" :aria-label="label">
    <button type="button" :disabled="modelValue <= min" aria-label="Diminuir" @click="change(-1)">−</button>
    <output aria-live="polite">{{ modelValue === '' ? '—' : modelValue }}</output>
    <button type="button" :disabled="modelValue >= max" aria-label="Aumentar" @click="change(1)">+</button>
  </div>
</template>
