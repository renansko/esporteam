<script setup>
import { ref, computed } from 'vue'
import { useAppStore } from '../stores/app'
import { fmtStatus } from '../mock/i18n'
import Icon from './Icon.vue'

const props = defineProps({ idea: { type: Object, required: true } })

const store = useAppStore()
const open = ref(false)

const COLORS = {
  analysis:    { bg: 'color-mix(in oklab, #4f7cd6 14%, var(--bg-elev))', fg: '#4a6dba' },
  planned:     { bg: 'var(--accent-soft)',                                fg: 'var(--ink)' },
  development: { bg: 'var(--gold-bg)',                                    fg: 'var(--gold)' },
  shipped:     { bg: 'color-mix(in oklab, #2f7d56 14%, var(--bg-elev))',  fg: 'var(--success)' },
}

const c = computed(() => COLORS[props.idea.status])
const lang = computed(() => store.lang)

function pickStatus(k, e) {
  e.stopPropagation()
  store.setStatus(props.idea.id, k)
  open.value = false
}
</script>

<template>
  <div class="no-row-click" style="position: relative">
    <span class="pill"
          :style="{ background: c.bg, color: c.fg, borderColor: 'transparent', cursor: 'pointer' }"
          @click.stop="open = !open">
      <span class="dot" />
      {{ fmtStatus(idea.status, lang) }}
      <Icon name="chevronDown" :size="10" />
    </span>
    <template v-if="open">
      <div style="position: fixed; inset: 0; z-index: 50" @click="open = false" />
      <div :style="{
        position: 'absolute', top: 'calc(100% + 4px)', left: 0, zIndex: 51,
        background: 'var(--bg-elev)', border: '1px solid var(--border-strong)',
        borderRadius: 'var(--r-3)', padding: '4px', minWidth: '160px', boxShadow: 'var(--shadow-pop)'
      }">
        <div v-for="k in ['analysis', 'planned', 'development', 'shipped']" :key="k"
             @click="pickStatus(k, $event)"
             :style="{ padding: '6px 8px', borderRadius: '4px', cursor: 'pointer', fontSize: '13px', display: 'flex', alignItems: 'center', gap: '8px' }"
             @mouseenter="$event.currentTarget.style.background = 'var(--accent-soft)'"
             @mouseleave="$event.currentTarget.style.background = 'transparent'">
          <span :style="{ width: '7px', height: '7px', borderRadius: '50%', background: COLORS[k].fg }" />
          {{ fmtStatus(k, lang) }}
        </div>
      </div>
    </template>
  </div>
</template>
