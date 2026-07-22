<script setup>
const props = defineProps({ modelValue: { type: Number, required: true }, min: { type: Number, default: 0 }, max: { type: Number, default: 100 }, step: { type: Number, default: 1 }, label: { type: String, required: true }, suffix: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue'])
const adjust = delta => emit('update:modelValue', Math.min(props.max, Math.max(props.min, props.modelValue + delta)))
</script>

<template>
  <label class="ui-slider"><span>{{ label }} <output>{{ modelValue }}{{ suffix }}</output></span><span class="ui-slider__control"><button type="button" :disabled="modelValue <= min" aria-label="Diminuir valor" @click.prevent="adjust(-step)">−</button><input type="range" :value="modelValue" :min="min" :max="max" :step="step" @input="$emit('update:modelValue', Number($event.target.value))"><button type="button" :disabled="modelValue >= max" aria-label="Aumentar valor" @click.prevent="adjust(step)">+</button></span></label>
</template>
