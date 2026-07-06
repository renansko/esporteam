<script setup>
import { computed } from 'vue'
import { fmtSource } from '../mock/i18n'

const props = defineProps({
  source: { type: String, required: true },
  lang:   { type: String, required: true },
})

const COLORS = {
  support:     { bg: 'color-mix(in oklab, #4f7cd6 14%, var(--bg-elev))', fg: '#4a6dba', border: 'color-mix(in oklab, #4f7cd6 30%, transparent)' },
  sales:       { bg: 'color-mix(in oklab, #b18a3a 18%, var(--bg-elev))', fg: 'var(--gold)', border: 'color-mix(in oklab, #b18a3a 32%, transparent)' },
  form:        { bg: 'var(--accent-soft)', fg: 'var(--ink-2)', border: 'var(--border)' },
  public_form: { bg: 'color-mix(in oklab, #8a5dd0 14%, var(--bg-elev))', fg: '#7a4cc4', border: 'color-mix(in oklab, #8a5dd0 28%, transparent)' },
  social:      { bg: 'color-mix(in oklab, #8a5dd0 14%, var(--bg-elev))', fg: '#7a4cc4', border: 'color-mix(in oklab, #8a5dd0 28%, transparent)' },
  manual:      { bg: 'color-mix(in oklab, #2f7d56 14%, var(--bg-elev))', fg: 'var(--success)', border: 'color-mix(in oklab, #2f7d56 28%, transparent)' },
  csv:         { bg: 'var(--accent-soft)', fg: 'var(--ink-2)', border: 'var(--border)' },
}

const colors = computed(() => COLORS[props.source] || COLORS.form)
const label = computed(() => fmtSource(props.source, props.lang))
</script>

<template>
  <span class="pill" :style="{ background: colors.bg, color: colors.fg, borderColor: colors.border }">
    {{ label }}
  </span>
</template>
