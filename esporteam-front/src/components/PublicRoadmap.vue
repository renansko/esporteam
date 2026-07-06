<script setup>
import { ref, computed, watch } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang, fmtStatus } from '../mock/i18n'
import { SEED_WORKSPACE } from '../mock/data'
import Icon from './Icon.vue'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const cols = ['analysis', 'planned', 'development', 'shipped']

const grouped = computed(() => {
  const g = { analysis: [], planned: [], development: [], shipped: [] }
  store.ideas.forEach(i => { if (g[i.status]) g[i.status].push(i) })
  Object.keys(g).forEach(k => g[k].sort((a, b) => b.score - a.score))
  return g
})

const voteFor = ref(null)
const drag = ref({ id: null, over: null })
const bumpedIds = ref([])

const prevScores = {}
watch(() => store.ideas.map(i => ({ id: i.id, score: i.score })), (ideas) => {
  const bumps = []
  ideas.forEach(i => {
    const p = prevScores[i.id]
    if (p != null && i.score > p + 0.1) bumps.push(i.id)
    prevScores[i.id] = i.score
  })
  if (bumps.length) {
    bumpedIds.value = bumps
    setTimeout(() => { bumpedIds.value = [] }, 700)
  }
}, { deep: true, immediate: true })

function onDragStart(e, id) {
  drag.value = { id, over: null }
  e.dataTransfer.effectAllowed = 'move'
}
function onDragOver(e, col) {
  e.preventDefault()
  if (drag.value.over !== col) drag.value = { ...drag.value, over: col }
}
function onDrop(e, col) {
  e.preventDefault()
  if (drag.value.id) store.setStatus(drag.value.id, col)
  drag.value = { id: null, over: null }
}
function onDragEnd() { drag.value = { id: null, over: null } }
function onDragLeave(col) {
  if (drag.value.over === col) drag.value = { ...drag.value, over: null }
}

function clickCard(e, idea) {
  if (e.target.closest('.vote-btn')) return
  store.selectIdea(idea.id)
}

function isVoted(ideaId) {
  return store.votes.some(v => v.ideaId === ideaId)
}

// vote modal state
const voteEmail = ref('')
const voteError = ref('')
watch(voteFor, () => { voteEmail.value = ''; voteError.value = '' })

function submitVote() {
  if (!voteFor.value) return
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(voteEmail.value)) {
    voteError.value = lang.value === 'pt' ? 'Email inválido.' : 'Invalid email.'
    return
  }
  const dup = store.votes.some(v => v.ideaId === voteFor.value.id && v.email.toLowerCase() === voteEmail.value.toLowerCase())
  if (dup) {
    voteError.value = pickLang(STR.roadmap_vote_dup, lang.value)
    return
  }
  store.vote(voteFor.value.id, voteEmail.value)
  voteFor.value = null
}
</script>

<template>
  <div class="page-hd">
    <div class="title-block">
      <div class="h-eyebrow" style="margin-bottom: 6px">
        {{ store.publicMode
          ? (lang === 'pt' ? 'Roadmap público' : 'Public roadmap')
          : (lang === 'pt' ? 'Visão de cliente · Edição interna' : 'Customer view · Internal editing') }}
      </div>
      <h1 class="h-display">{{ t('roadmap_title') }}</h1>
      <p>{{ t('roadmap_subtitle') }}</p>
    </div>
    <div class="row gap-2">
      <span class="pill mono"><Icon name="link" :size="11" /> {{ SEED_WORKSPACE.publicRoadmapUrl }}</span>
      <button class="btn"><Icon name="share" /> {{ t('roadmap_share') }}</button>
    </div>
  </div>

  <div class="page-body" style="background: var(--bg)">
    <div class="kanban">
      <div v-for="col in cols" :key="col" style="min-width: 0">
        <div class="kcol-hd">
          <span>{{ fmtStatus(col, lang) }}</span>
          <span class="count mono">{{ grouped[col].length }}</span>
        </div>
        <div :class="['kcol', { 'drop-target': drag.over === col }]"
             @dragover="onDragOver($event, col)"
             @drop="onDrop($event, col)"
             @dragleave="onDragLeave(col)">
          <div v-for="i in grouped[col]" :key="i.id"
               :class="['kcard', { dragging: drag.id === i.id, voted: isVoted(i.id), bumped: bumpedIds.includes(i.id) }]"
               draggable="true"
               @dragstart="onDragStart($event, i.id)"
               @dragend="onDragEnd"
               @click="clickCard($event, i)">
            <h4>{{ pickLang(i.title, lang) }}</h4>
            <p class="line-clamp-2">{{ pickLang(i.description, lang) }}</p>
            <div class="ft">
              <div class="row gap-2">
                <span v-for="tag in (i.tags || []).slice(0, 2)" :key="tag" class="mono dim" style="font-size: 10.5px">#{{ tag }}</span>
              </div>
              <button class="btn btn-sm vote-btn" @click.stop="voteFor = i">
                <Icon name="vote" :size="11" />
                <span class="n">{{ i.votes || 0 }}</span>
              </button>
            </div>
          </div>
          <div v-if="grouped[col].length === 0" class="dim" style="font-size: 12px; text-align: center; padding: 18px">
            {{ t('empty_state') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <div :class="['modal-back', { open: !!voteFor }]" @click="voteFor = null">
    <form v-if="voteFor" class="modal" @click.stop @submit.prevent="submitVote">
      <div class="h-eyebrow" style="margin-bottom: 6px; color: var(--gold)">
        <Icon name="vote" :size="11" /> {{ t('roadmap_vote_modal_title') }}
      </div>
      <h2>{{ pickLang(voteFor.title, lang) }}</h2>
      <p class="sub">{{ pickLang(voteFor.description, lang) }}</p>

      <label style="font-size: 12px; color: var(--ink-2); display: block; margin-bottom: 6px">{{ t('roadmap_vote_email') }}</label>
      <input class="input" type="email" autofocus
             placeholder="voce@empresa.com"
             v-model="voteEmail" @input="voteError = ''" />
      <div v-if="voteError" style="margin-top: 8px; color: var(--danger); font-size: 12px">{{ voteError }}</div>

      <div class="row" style="margin-top: 16px; justify-content: space-between; align-items: center">
        <div class="mono dim" style="font-size: 11px">
          {{ voteFor.votes || 0 }} {{ lang === 'pt' ? 'votos até agora' : 'votes so far' }}
        </div>
        <div class="row gap-2">
          <button type="button" class="btn" @click="voteFor = null">{{ t('idea_cancel') }}</button>
          <button type="submit" class="btn btn-gold">{{ t('roadmap_vote_submit') }}</button>
        </div>
      </div>
    </form>
  </div>
</template>
