<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 16 },
  stroke: { type: [Number, String], default: 1.6 },
  fill: { type: String, default: 'none' },
})

const PATHS = {
  inbox:       { d: ['M2 3h12v6h-3l-1 2H6l-1-2H2z', 'M2 9v4h12V9'] },
  ideas:       { d: ['M8 1.5v1.5', 'M3.5 3.5l1 1', 'M12.5 3.5l-1 1', 'M1.5 8H3', 'M13 8h1.5', 'M5.5 11.5h5', 'M6 13.5h4', 'M8 4a3 3 0 0 0-2 5.2c.4.4.5.8.5 1.3h3c0-.5.1-.9.5-1.3A3 3 0 0 0 8 4z'] },
  competitors: { d: ['M3 13V6', 'M8 13V3', 'M13 13V8', 'M1.5 13.5h13'] },
  roadmap:     { d: ['M2 3h3v3H2z', 'M6.5 3h3v3h-3z', 'M11 3h3v3h-3z', 'M2 10h3v3H2z', 'M6.5 10h3v3h-3z'] },
  settings:    { d: ['M6.5 1.5h3l.5 1.7 1.5.8 1.6-.4 1.4 2.6-1.2 1.2v1.7l1.2 1.2-1.4 2.6-1.6-.4-1.5.8-.5 1.7h-3l-.5-1.7-1.5-.8-1.6.4L1.5 9.8 2.7 8.6V6.9L1.5 5.7 2.9 3.1l1.6.4 1.5-.8z', 'M8 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z'] },
  search:      { d: ['M11 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z', 'M10 10l3 3'] },
  plus:        { d: ['M8 3v10', 'M3 8h10'] },
  sparkles:    { d: ['M5 2v3', 'M3.5 3.5h3', 'M11 8v3', 'M9.5 9.5h3', 'M7 10l1.5 3.5 3.5 1.5-3.5 1.5L7 20l-1.5-3.5L2 15l3.5-1.5z'], stroke: 1.4 },
  check:       { d: ['M3 8l3 3 7-7'] },
  x:           { d: ['M3.5 3.5l9 9', 'M12.5 3.5l-9 9'] },
  chevron:     { d: ['M5 3l5 5-5 5'] },
  chevronDown: { d: ['M3 5l5 5 5-5'] },
  arrowUp:     { d: ['M8 13V3', 'M3 8l5-5 5 5'] },
  drag:        { d: ['M6 4h.01', 'M10 4h.01', 'M6 8h.01', 'M10 8h.01', 'M6 12h.01', 'M10 12h.01'], stroke: 2.6 },
  vote:        { d: ['M8 13V3', 'M3 8l5-5 5 5'] },
  edit:        { d: ['M11 2.5l2.5 2.5L6 12.5H3.5V10z'] },
  share:       { d: ['M5 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0z', 'M15 4a2 2 0 1 1-4 0 2 2 0 0 1 4 0z', 'M15 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0z', 'M5 8l6-3', 'M5 8l6 3'] },
  link:        { d: ['M7 9l2-2', 'M9 5l1-1a2.83 2.83 0 0 1 4 4l-1 1', 'M2 11l1-1a2.83 2.83 0 1 1 4 4l-1 1', 'M5 6l-1 1a2.83 2.83 0 0 0 4 4l1-1', 'M11 9l1 1'] },
  filter:      { d: ['M2 3h12l-4.5 6v4l-3 1V9z'] },
  mail:        { d: ['M2 4h12v8H2z', 'M2 5l6 4 6-4'] },
  bolt:        { d: ['M9 1L3 9h4l-1 6 6-8H8z'] },
  back:        { d: ['M8 3L3 8l5 5', 'M3 8h11'] },
  external:    { d: ['M6 3H3v10h10v-3', 'M9 3h4v4', 'M8 8l5-5'] },
}

const icon = computed(() => PATHS[props.name] || { d: [] })
const strokeWidth = computed(() => icon.value.stroke ?? props.stroke)
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 16 16" :fill="fill"
       stroke="currentColor" :stroke-width="strokeWidth"
       stroke-linecap="round" stroke-linejoin="round">
    <path v-for="(d, i) in icon.d" :key="i" :d="d" />
  </svg>
</template>
