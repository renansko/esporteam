<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang, fmtStatus, fmtOrigin } from '../mock/i18n'
import { SEED_WORKSPACE } from '../mock/data'
import { feedbackCount } from '../stores/app'
import Icon from './Icon.vue'
import StatusSelect from './StatusSelect.vue'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const filter = ref('all')
const bumpedId = ref(null)

const sorted = computed(() => {
  return store.ideas
    .filter(i => filter.value === 'all' ? true : i.status === filter.value)
    .slice().sort((a, b) => b.score - a.score)
})

const maxScore = computed(() => Math.max(...store.ideas.map(x => x.score), 1))
const justClustered = computed(() => store.clusteringState === 'done')

const prevScores = {}
watch(() => store.ideas.map(i => ({ id: i.id, score: i.score })), (ideas) => {
  let topBump = null
  ideas.forEach(i => {
    const prev = prevScores[i.id]
    if (prev != null && i.score > prev + 0.1) topBump = i.id
    prevScores[i.id] = i.score
  })
  if (topBump) {
    bumpedId.value = topBump
    setTimeout(() => { bumpedId.value = null }, 1400)
  }
}, { deep: true, immediate: true })

const editingId = ref(null)
const editVal = ref('')

function startEdit(idea) {
  editingId.value = idea.id
  editVal.value = pickLang(idea.title, lang.value)
  nextTick(() => {
    const el = document.querySelector('.list-row.editing input')
    if (el) el.focus()
  })
}
function saveTitle(idea) {
  const patch = { title: { ...idea.title, [lang.value]: editVal.value } }
  store.updateIdea(idea.id, patch)
  editingId.value = null
}

function onRowClick(e, idea) {
  if (e.target.closest('.no-row-click')) return
  store.selectIdea(idea.id)
}

function fbCount(idea) { return feedbackCount(store, idea.id) }
</script>

<template>
  <div class="page-hd">
    <div class="title-block">
      <div class="h-eyebrow" style="margin-bottom: 6px">Backlog</div>
      <h1 class="h-display">{{ t('ideas_title') }}</h1>
      <p>{{ t('ideas_subtitle') }}</p>
    </div>
    <div class="row gap-2" style="flex-shrink: 0">
      <span class="pill mono"><Icon name="link" :size="11" /> {{ SEED_WORKSPACE.publicRoadmapUrl }}</span>
    </div>
  </div>

  <div class="ideas-toolbar">
    <div class="chip-group">
      <div v-for="k in ['all', 'analysis', 'planned', 'development', 'shipped']" :key="k"
           :class="['chip', { active: filter === k }]" @click="filter = k">
        {{ k === 'all' ? t('ideas_filter_all') : fmtStatus(k, lang) }}
      </div>
    </div>
    <div style="flex: 1" />
    <div class="mono dim" style="font-size: 11.5px">
      {{ sorted.length }} {{ lang === 'pt' ? 'itens' : 'items' }} ·
      <span style="color: var(--ink-2)">{{ lang === 'pt' ? 'ordenados por score' : 'sorted by score' }}</span>
    </div>
  </div>

  <div v-if="justClustered" :style="{ padding: '10px 28px', background: 'var(--gold-bg)', borderBottom: '1px solid var(--gold-soft)', color: 'var(--gold)', fontSize: '13px', display: 'flex', alignItems: 'center', gap: '10px' }">
    <Icon name="sparkles" />
    <span>
      <b>{{ lang === 'pt' ? 'Análise concluída.' : 'Analysis complete.' }}</b>
      {{ lang === 'pt'
        ? 'A IA agrupou 50 ideias em 13 itens do roadmap e calculou um score RICE para cada um. Os scores combinam alcance, impacto, confiança, esforço e votos públicos.'
        : 'AI clustered 50 ideas into 13 roadmap items and computed a RICE score for each. Scores combine reach, impact, confidence, effort, and public votes.' }}
    </span>
  </div>

  <div class="page-body">
    <div class="list">
      <div class="list-row ideas-row no-hover" style="background: transparent; cursor: default; border-bottom-color: var(--border-strong)">
        <div />
        <div class="h-eyebrow">{{ t('ideas_col_title') }}</div>
        <div class="h-eyebrow">{{ t('ideas_col_status') }}</div>
        <div class="h-eyebrow">{{ t('ideas_col_feedback') }}</div>
        <div class="h-eyebrow">{{ t('ideas_col_votes') }}</div>
        <div class="h-eyebrow" style="text-align: right">{{ t('ideas_col_score') }}</div>
      </div>

      <div v-for="(idea, idx) in sorted" :key="idea.id"
           :class="['list-row', 'ideas-row', { editing: editingId === idea.id }]"
           :style="{ position: 'relative', animation: bumpedId === idea.id ? 'bump 0.6s cubic-bezier(0.2,0.8,0.2,1)' : undefined }"
           @click="onRowClick($event, idea)">
        <div class="mono dim" style="font-size: 11.5px">#{{ String(idx + 1).padStart(2, '0') }}</div>

        <div>
          <input v-if="editingId === idea.id"
                 class="input input-bare no-row-click"
                 v-model="editVal"
                 @click.stop
                 @blur="saveTitle(idea)"
                 @keydown.enter="saveTitle(idea)"
                 @keydown.escape="editingId = null"
                 :style="{ fontSize: '14px', fontWeight: 540, width: '100%' }" />
          <div v-else
               @dblclick.stop="startEdit(idea)"
               :style="{ fontSize: '14px', fontWeight: 540, lineHeight: 1.3 }">
            {{ pickLang(idea.title, lang) }}
            <span v-if="idea.origin === 'competitor_gap'" class="pill pill-gold" style="margin-left: 8px; font-size: 10px">
              {{ fmtOrigin(idea.origin, lang) }}
            </span>
          </div>
          <div class="line-clamp-2" :style="{ marginTop: '4px', fontSize: '11.5px', color: 'var(--ink-3)' }">
            {{ pickLang(idea.description, lang) }}
          </div>
        </div>

        <StatusSelect :idea="idea" />

        <div class="mono dim" style="font-size: 12.5px">{{ fbCount(idea) }}</div>

        <div class="mono" :style="{ fontSize: '12.5px', display: 'flex', alignItems: 'center', gap: '4px', color: idea.votes ? 'var(--gold)' : 'var(--ink-4)' }">
          <Icon v-if="idea.votes > 0" name="vote" :size="11" />
          {{ idea.votes || 0 }}
        </div>

        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; position: relative">
          <div class="score-bar"><i :style="{ width: Math.round((idea.score / maxScore) * 100) + '%' }" /></div>
          <div class="score-num" style="width: 44px; text-align: right">{{ idea.score.toFixed(1) }}</div>
          <span v-if="bumpedId === idea.id" class="blip">↑</span>
        </div>
      </div>
    </div>
  </div>
</template>
