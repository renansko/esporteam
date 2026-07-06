<script setup>
import { ref, computed, watch } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'
import { SEED_CHANGELOG_DRAFT, SEED_COMANDERO_PREDICTION } from '../mock/data'
import Icon from './Icon.vue'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const competitor = computed(() =>
  store.competitors.find(c => c.id === store.selectedCompetitorId) || store.competitors[0])

const features = computed(() =>
  store.competitorFeatures.filter(f => f.competitorId === competitor.value.id))

const pending = computed(() =>
  competitor.value.id === 'comp-3' ? store.pendingComanderoFeatures : null)

const showPaste = computed(() =>
  competitor.value.id === 'comp-3' && !pending.value)

const pasteText = ref(pickLang(SEED_CHANGELOG_DRAFT, lang.value))
watch(lang, (l) => { pasteText.value = pickLang(SEED_CHANGELOG_DRAFT, l) })

function runAnalysis() {
  store.setPasteAnalyzing(true)
  setTimeout(() => {
    store.setPasteAnalyzing(false)
    store.setPendingFeatures(SEED_COMANDERO_PREDICTION.map(p => ({ ...p, revealed: false })))
    SEED_COMANDERO_PREDICTION.forEach((_, idx) => {
      setTimeout(() => store.bumpRevealed(idx), 220 + idx * 230)
    })
  }, 1800)
}

function gapCount(cid) {
  return store.competitorFeatures.filter(f => f.competitorId === cid && f.match === 'gap').length
}
function cFeatureCount(cid) {
  return store.competitorFeatures.filter(f => f.competitorId === cid).length
}

const COLS = 'minmax(0, 1.4fr) 92px minmax(0, 1fr) auto'

const visibleFeatures = computed(() => {
  if (pending.value) return pending.value.slice(0, store.revealedPending)
  return features.value
})

function ideaFor(f) {
  return f.linkedIdea && store.ideas.find(i => i.id === f.linkedIdea)
}

function openIdea(idea) {
  store.selectIdea(idea.id)
  store.setPage('ideas')
}

function promote(f, idx) {
  if (pending.value) store.promotePendingFeature(f, idx)
  else store.promoteFeature(f.id)
}
</script>

<template>
  <div class="page-hd">
    <div class="title-block">
      <div class="h-eyebrow" style="margin-bottom: 6px">{{ lang === 'pt' ? 'Inteligência' : 'Intelligence' }}</div>
      <h1 class="h-display">{{ t('comp_title') }}</h1>
      <p>{{ t('comp_subtitle') }}</p>
    </div>
    <button class="btn"><Icon name="plus" /> {{ t('comp_add') }}</button>
  </div>

  <div :style="{ display: 'grid', gridTemplateColumns: '220px 1fr', height: '100%', minHeight: 0, flex: 1, overflow: 'hidden' }">
    <div class="surf" style="border-right: 1px solid var(--border); overflow: auto; border-bottom: none">
      <div v-for="c in store.competitors" :key="c.id"
           @click="store.selectCompetitor(c.id)"
           :class="['sb-item', { active: store.selectedCompetitorId === c.id }]"
           style="margin: 8px 10px; padding: 10px 12px">
        <div style="flex: 1">
          <div style="font-weight: 540; font-size: 13.5px">{{ c.name }}</div>
          <div class="mono dim" style="font-size: 11px">{{ c.url }}</div>
        </div>
        <span v-if="c.analyzed" class="pill mono" style="font-size: 10px">
          {{ cFeatureCount(c.id) }} {{ lang === 'pt' ? 'features' : 'features' }}<template v-if="gapCount(c.id) > 0"> · {{ gapCount(c.id) }} gap{{ gapCount(c.id) === 1 ? '' : 's' }}</template>
        </span>
        <span v-else class="pill pill-gold mono" style="font-size: 10px">{{ lang === 'pt' ? 'novo' : 'new' }}</span>
      </div>
    </div>

    <div class="page-body" style="padding: 20px 22px 24px; min-width: 0">
      <div style="display: flex; align-items: baseline; gap: 12px; margin-bottom: 4px">
        <h2 style="margin: 0; font-size: 22px; font-weight: 580; letter-spacing: -0.015em">{{ competitor.name }}</h2>
        <a :href="`https://${competitor.url}`" target="_blank" class="mono dim"
           style="font-size: 12px; text-decoration: none" @click.prevent>
          {{ competitor.url }} <Icon name="external" :size="10" />
        </a>
      </div>

      <div v-if="showPaste" class="card" style="padding: 18px; margin-top: 20px">
        <div class="h-eyebrow" style="margin-bottom: 8px">{{ t('comp_paste_label') }}</div>
        <textarea class="textarea" rows="14"
                  v-model="pasteText"
                  :placeholder="t('comp_paste_label') + '…'" />
        <div class="row" style="margin-top: 14px; justify-content: space-between">
          <div class="dim" style="font-size: 12px">
            {{ pasteText.length }} {{ lang === 'pt' ? 'caracteres' : 'characters' }}
          </div>
          <button class="btn btn-gold" @click="runAnalysis" :disabled="store.pasteAnalyzing">
            <template v-if="store.pasteAnalyzing">
              <span :style="{ width: '12px', height: '12px', border: '1.6px solid rgba(255,255,255,0.5)', borderTopColor: '#fff', borderRadius: '50%', display: 'inline-block', animation: 'spin 0.7s linear infinite' }" />
              {{ t('comp_analyzing') }}
            </template>
            <template v-else>
              <Icon name="sparkles" /> {{ t('comp_analyze') }}
            </template>
          </button>
        </div>
      </div>

      <div v-else style="margin-top: 18px">
        <div class="list-row no-hover"
             :style="{ gridTemplateColumns: COLS, background: 'transparent', cursor: 'default', borderBottom: '1px solid var(--border-strong)', padding: '10px 14px' }">
          <div class="h-eyebrow">{{ t('comp_col_feature') }}</div>
          <div class="h-eyebrow">{{ t('comp_col_status') }}</div>
          <div class="h-eyebrow">{{ t('comp_col_idea') }}</div>
          <div class="h-eyebrow" style="text-align: right">{{ lang === 'pt' ? 'Ação' : 'Action' }}</div>
        </div>

        <div v-if="visibleFeatures.length === 0" style="padding: 40px 0; text-align: center; color: var(--ink-3)">
          {{ t('comp_analyzing') }}…
        </div>

        <div v-for="(f, idx) in visibleFeatures" :key="f.id || ('pending-' + idx)"
             :class="['list-row no-hover', { reveal: !!pending }]"
             :style="{ gridTemplateColumns: COLS, cursor: 'default', alignItems: 'center', animationDelay: pending ? (idx * 0.08) + 's' : '0s' }">
          <div style="min-width: 0">
            <div style="font-size: 13.5px; font-weight: 540; line-height: 1.35">{{ pickLang(f.name, lang) }}</div>
            <div v-if="f.description && pickLang(f.description, lang)"
                 class="line-clamp-2" style="font-size: 11.5px; color: var(--ink-3); margin-top: 2px">
              {{ pickLang(f.description, lang) }}
            </div>
          </div>
          <div>
            <span v-if="f.match === 'match'"   class="pill pill-success" style="font-size: 11px"><Icon name="check" :size="10" /> {{ t('comp_match') }}</span>
            <span v-else-if="f.match === 'partial'" class="pill pill-warn" style="font-size: 11px">{{ t('comp_partial') }}</span>
            <span v-else class="pill pill-danger" style="font-size: 11px">{{ t('comp_gap') }}</span>
          </div>
          <div style="min-width: 0">
            <template v-if="ideaFor(f)">
              <div class="row gap-2" style="cursor: pointer; min-width: 0" @click="openIdea(ideaFor(f))">
                <span class="mono dim" style="font-size: 10.5px; flex-shrink: 0">{{ ideaFor(f).id }}</span>
                <span class="truncate" style="font-size: 12.5px; min-width: 0">{{ pickLang(ideaFor(f).title, lang) }}</span>
              </div>
            </template>
            <template v-else>
              <span class="dim" style="font-size: 12px">—</span>
            </template>
          </div>
          <div style="text-align: right">
            <template v-if="f.match === 'gap'">
              <span v-if="f.promoted" class="pill pill-success" style="font-size: 11px">
                <Icon name="check" :size="10" /> {{ t('comp_promoted') }}
              </span>
              <button v-else class="btn btn-sm btn-gold" @click="promote(f, idx)">
                <Icon name="plus" /> {{ t('comp_promote') }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
