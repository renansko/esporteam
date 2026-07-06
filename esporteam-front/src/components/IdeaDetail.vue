<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAppStore, feedbacksForIdea, votesForIdea } from '../stores/app'
import { STR, pickLang, fmtOrigin } from '../mock/i18n'
import Icon from './Icon.vue'
import StatusSelect from './StatusSelect.vue'
import SourcePill from './SourcePill.vue'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const idea = computed(() => store.ideas.find(i => i.id === store.selectedIdeaId))
const open = computed(() => !!idea.value)
const fbs = computed(() => idea.value ? feedbacksForIdea(store, idea.value.id) : [])
const votes = computed(() => idea.value ? votesForIdea(store, idea.value.id) : [])

const edit = ref(false)

function close() {
  store.selectIdea(null)
  edit.value = false
}

function onKey(e) { if (e.key === 'Escape') close() }
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))

function fmtVal(val, step) {
  return step < 1 ? val.toFixed(2) : String(val)
}

function patchBreakdown(field, v) {
  if (!idea.value) return
  store.updateIdea(idea.value.id, { breakdown: { [field]: Number(v) } })
}
</script>

<template>
  <div :class="['drawer-back', { open }]" @click="close" />
  <div :class="['drawer', { open }]">
    <template v-if="idea">
      <div class="drawer-hd">
        <div style="flex: 1; min-width: 0">
          <div class="row gap-2" style="margin-bottom: 6px">
            <span class="pill mono" style="font-size: 10px">{{ idea.id }}</span>
            <span class="pill" style="font-size: 10px">{{ fmtOrigin(idea.origin, lang) }}</span>
            <StatusSelect :idea="idea" />
            <span v-if="idea.votes > 0" class="pill pill-gold" style="font-size: 10px">
              <Icon name="vote" :size="10" /> {{ idea.votes }} {{ lang === 'pt' ? 'votos' : 'votes' }}
            </span>
          </div>
          <h2 style="margin: 0; font-size: 19px; line-height: 1.25; font-weight: 580">
            {{ pickLang(idea.title, lang) }}
          </h2>
          <p style="margin: 8px 0 0; font-size: 13px; color: var(--ink-2); line-height: 1.5">
            {{ pickLang(idea.description, lang) }}
          </p>
        </div>
        <button class="btn btn-ghost btn-sm" @click="close" aria-label="Close"><Icon name="x" /></button>
      </div>

      <div class="drawer-body scroll">
        <section>
          <div class="row" style="justify-content: space-between; margin-bottom: 10px">
            <h3 style="margin: 0">{{ t('idea_breakdown') }}</h3>
            <button class="btn btn-sm btn-ghost" @click="edit = !edit">
              <Icon name="edit" /> {{ edit ? t('idea_cancel') : (lang === 'pt' ? 'Editar' : 'Edit') }}
            </button>
          </div>

          <div class="brk">
            <template v-for="(cfg) in [
              { key: 'reach',      label: t('idea_reach'),      max: 10, step: 1 },
              { key: 'impact',     label: t('idea_impact'),     max: 10, step: 1 },
              { key: 'confidence', label: t('idea_confidence'), max: 1,  step: 0.05, suffix: '' },
              { key: 'effort',     label: t('idea_effort'),     max: 10, step: 1 },
            ]" :key="cfg.key">
              <div class="brk-cell">
                <div class="l">{{ cfg.label }}</div>
                <div v-if="!edit" class="v">
                  {{ fmtVal(idea.breakdown[cfg.key], cfg.step) }}<span style="font-size: 12px; color: var(--ink-4); margin-left: 2px">{{ cfg.suffix === '' ? '' : '/' + cfg.max }}</span>
                </div>
                <div v-else style="display: flex; align-items: center; gap: 8px">
                  <input type="range" :min="cfg.step" :max="cfg.max" :step="cfg.step"
                         :value="idea.breakdown[cfg.key]"
                         @input="patchBreakdown(cfg.key, $event.target.value)"
                         style="flex: 1; accent-color: var(--gold)" />
                  <div class="v" style="font-size: 14px; min-width: 36px; text-align: right">{{ fmtVal(idea.breakdown[cfg.key], cfg.step) }}</div>
                </div>
              </div>
            </template>
          </div>

          <div style="margin-top: 14px; padding: 12px 14px; background: var(--bg-sunken); border: 1px solid var(--border); border-radius: var(--r-3)">
            <div class="row" style="justify-content: space-between">
              <div class="mono dim" style="font-size: 11.5px">{{ t('idea_formula') }}</div>
              <div class="row gap-2">
                <div class="mono dim" style="font-size: 11.5px">=</div>
                <div class="score-num" style="font-size: 22px; color: var(--gold)">{{ idea.score.toFixed(1) }}</div>
              </div>
            </div>
            <div v-if="idea.votes > 0" class="mono dim" style="font-size: 11px; margin-top: 6px">
              {{ t('idea_vote_boost') }}: +{{ (Math.sqrt(idea.votes) * 12).toFixed(1) }}
            </div>
          </div>

          <div style="margin-top: 14px; font-size: 12.5px; color: var(--ink-2); line-height: 1.55">
            <span class="h-eyebrow" style="display: block; margin-bottom: 4px">{{ t('idea_rationale') }}</span>
            {{ pickLang(idea.rationale, lang) }}
          </div>
        </section>

        <section>
          <h3 style="display: flex; justify-content: space-between; align-items: baseline">
            <span>{{ t('idea_sources') }}</span>
            <span class="mono dim" style="font-size: 11px">{{ fbs.length }}</span>
          </h3>
          <div v-if="fbs.length === 0" class="dim" style="font-size: 13px">{{ t('empty_state') }}</div>
          <div v-for="f in fbs" :key="f.id" class="fb-card">
            <div class="meta">
              <SourcePill :source="f.source" :lang="lang" />
              <span class="mail">{{ f.author }}</span>
              <span style="margin-left: auto">{{ f.created }}</span>
            </div>
            <div class="body">{{ pickLang(f.text, lang) }}</div>
          </div>
        </section>

        <section v-if="votes.length > 0">
          <h3 style="display: flex; justify-content: space-between; align-items: baseline">
            <span>{{ t('idea_public_votes') }}</span>
            <span class="mono dim" style="font-size: 11px">{{ votes.length }}</span>
          </h3>
          <div style="display: flex; flex-wrap: wrap; gap: 6px">
            <span v-for="(v, i) in votes" :key="i" class="pill mono pill-gold" style="font-size: 11px">
              <Icon name="vote" :size="10" /> {{ v.email }}
            </span>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>
