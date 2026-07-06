<script setup>
import { computed, watch } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'
import Icon from './Icon.vue'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)
const open = computed(() => store.loopState !== 'idle')

const TARGET_ID = 'idea-13'
const target = computed(() => store.ideas.find(i => i.id === TARGET_ID))

let timers = []
function clearTimers() { timers.forEach(clearTimeout); timers = [] }

watch(() => store.loopState, (loopState) => {
  clearTimers()
  if (loopState === 'idle') return

  if (loopState === 'step1') {
    timers.push(setTimeout(() => store.setPage('roadmap'), 600))
    timers.push(setTimeout(() => store.setLoop('step2'), 1500))
  }
  if (loopState === 'step2') {
    const fakeEmails = [
      'ana@bistroflora.com.br', 'marco@gastropub.com', 'ju@docerianorte.com',
      'claudia@cafe.com', 'leo@bardogabriel.com.br', 'diego@pastel.com',
    ]
    fakeEmails.forEach((email, i) => {
      timers.push(setTimeout(() => store.vote(TARGET_ID, email), 200 + i * 340))
    })
    timers.push(setTimeout(() => store.setLoop('step3'), 2400))
  }
  if (loopState === 'step3') {
    timers.push(setTimeout(() => store.setLoop('step4'), 1100))
  }
  if (loopState === 'step4') {
    timers.push(setTimeout(() => store.setPage('ideas'), 700))
    timers.push(setTimeout(() => store.setLoop('done'), 2400))
  }
  if (loopState === 'done') {
    timers.push(setTimeout(() => store.setLoop('idle'), 1800))
  }
})

function stepStatus(n) {
  const order = { idle: 0, step1: 1, step2: 2, step3: 3, step4: 4, done: 5 }
  const cur = order[store.loopState]
  if (n < cur) return 'done'
  if (n === cur) return 'active'
  return ''
}

function close() { store.setLoop('idle') }

const showReveal = computed(() => ['step3', 'step4', 'done'].includes(store.loopState))
</script>

<template>
  <div :class="['loop-host', { open }]" @click="close">
    <div class="loop-card" @click.stop>
      <div class="h-eyebrow" style="color: var(--gold); margin-bottom: 8px; display: flex; align-items: center; gap: 8px">
        <Icon name="bolt" /> {{ t('loop_title') }}
      </div>
      <h2 style="margin: 0 0 4px; font-size: 22px">
        {{ lang === 'pt' ? 'Ideia → IA → Roadmap → Voto → Re-priorização' : 'Idea → AI → Roadmap → Vote → Re-prioritization' }}
      </h2>
      <p style="margin: 0 0 16px; color: var(--ink-3); font-size: 13px">{{ t('loop_subtitle') }}</p>

      <div :class="['loop-step', stepStatus(1)]">
        <div class="bullet">1</div>
        <div class="label">{{ t('loop_step_1') }}</div>
      </div>
      <div :class="['loop-step', stepStatus(2)]">
        <div class="bullet">2</div>
        <div class="label">{{ t('loop_step_2') }}</div>
        <div class="extra">{{ target ? `${target.votes || 0} ${lang === 'pt' ? 'votos' : 'votes'}` : '' }}</div>
      </div>
      <div :class="['loop-step', stepStatus(3)]">
        <div class="bullet">3</div>
        <div class="label">{{ t('loop_step_3') }}</div>
        <div class="extra">{{ target ? `${target.score.toFixed(1)} ${lang === 'pt' ? 'pontos' : 'points'}` : '' }}</div>
      </div>
      <div :class="['loop-step', stepStatus(4)]">
        <div class="bullet">4</div>
        <div class="label">{{ t('loop_step_4') }}</div>
      </div>

      <div v-if="target && showReveal" class="reveal"
           style="margin-top: 18px; padding: 12px 14px; background: var(--gold-bg); border: 1px solid var(--gold-soft); border-radius: var(--r-3)">
        <div class="row" style="justify-content: space-between; align-items: center">
          <div>
            <div class="h-eyebrow" style="color: var(--gold); margin-bottom: 2px">
              {{ lang === 'pt' ? 'Item impactado' : 'Affected item' }}
            </div>
            <div style="font-weight: 560; font-size: 14px">{{ pickLang(target.title, lang) }}</div>
          </div>
          <div style="text-align: right">
            <div class="h-eyebrow" style="margin-bottom: 2px">{{ lang === 'pt' ? 'novo score' : 'new score' }}</div>
            <div class="score-num" style="color: var(--gold); font-size: 22px">
              {{ target.score.toFixed(1) }} <span class="score-up" style="font-size: 13px">+{{ (Math.sqrt(target.votes || 0) * 12).toFixed(1) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="row" style="margin-top: 18px; justify-content: flex-end; gap: 8px">
        <button class="btn btn-ghost" @click="close">
          {{ store.loopState === 'done' ? (lang === 'pt' ? 'Fechar' : 'Close') : (lang === 'pt' ? 'Pular' : 'Skip') }}
        </button>
      </div>
    </div>
  </div>
</template>
