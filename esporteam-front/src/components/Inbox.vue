<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang, fmtSource } from '../mock/i18n'
import Icon from './Icon.vue'
import SourcePill from './SourcePill.vue'
import CreateIdeaModal from './CreateIdeaModal.vue'
import Skeleton from './Skeleton.vue'
import { useDelayedLoading } from '../composables/useDelayedLoading'

const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const query = ref('')
const showCreate = ref(false)

const SOURCES = ['all', 'manual', 'csv', 'public_form']

const filter = computed({
  get: () => store.inboxFilters.clustered,
  set: (v) => store.setInboxFilter('clustered', v),
})
const source = computed({
  get: () => store.inboxFilters.source,
  set: (v) => store.setInboxFilter('source', v),
})

const visibleList = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return store.inboxIdeas
  return store.inboxIdeas.filter(i => {
    const text = `${i.title ?? ''} ${i.description ?? ''} ${i.author_email ?? ''}`.toLowerCase()
    return text.includes(q)
  })
})

const total       = computed(() => store.inboxIdeas.length)
const clustered   = computed(() => store.inboxIdeas.filter(i => i.clustered).length)
const unclustered = computed(() => total.value - clustered.value)
const inboxSkeletonVisible = useDelayedLoading(() => store.inboxLoading)

function runAI() {
  if (store.clusteringState === 'running') return
  store.setClustering('running')
  setTimeout(() => {
    store.setClustering('done')
    setTimeout(() => store.setPage('ideas'), 600)
  }, 2500)
}

function formatDate(iso) {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleDateString(lang.value === 'pt' ? 'pt-BR' : 'en-US', {
      day: '2-digit', month: 'short',
    })
  } catch { return '' }
}

onMounted(() => {
  if (store.auth) store.loadInboxIdeas()
})
</script>

<template>
  <div class="page-hd">
    <div class="title-block">
      <div class="h-eyebrow" style="margin-bottom: 6px">{{ lang === 'pt' ? 'Captura' : 'Capture' }}</div>
      <h1 class="h-display">{{ t('inbox_title') }}</h1>
      <p>{{ t('inbox_subtitle') }}</p>
    </div>
    <div class="row gap-2">
      <button class="btn btn-sm" type="button" @click="showCreate = true">
        <Icon name="plus" /> {{ t('inbox_new') }}
      </button>
      <button
        class="btn btn-gold btn-lg"
        :disabled="store.clusteringState === 'running' || !unclustered"
        @click="runAI"
      >
        <template v-if="store.clusteringState === 'running'">
          <span aria-hidden="true">···</span>
          {{ t('inbox_analyzing') }}
        </template>
        <template v-else>
          <Icon name="sparkles" /> {{ t('inbox_analyze') }}
        </template>
      </button>
    </div>
  </div>

  <div class="inbox-toolbar">
    <div class="chip-group">
      <div v-for="k in ['all', 'unclustered', 'clustered']" :key="k"
           :class="['chip', { active: filter === k }]" @click="filter = k">
        {{ pickLang(STR['inbox_filter_' + k], lang) }}
      </div>
    </div>

    <div class="chip-group">
      <div v-for="src in SOURCES" :key="src"
           :class="['chip', { active: source === src }]" @click="source = src">
        {{ src === 'all' ? t('inbox_filter_all') : fmtSource(src, lang) }}
      </div>
    </div>

    <div style="flex: 1" />

    <div style="position: relative; width: 240px">
      <span style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: var(--ink-4)">
        <Icon name="search" />
      </span>
      <input class="input" :style="{ paddingLeft: '30px' }"
             :placeholder="t('inbox_search')" v-model="query" />
    </div>
  </div>

  <div class="inbox-stats">
    <b>{{ total }}</b> {{ t('inbox_count') }} ·
    <b>{{ clustered }}</b> {{ lang === 'pt' ? 'no roadmap' : 'on roadmap' }} ·
    <b>{{ unclustered }}</b> {{ lang === 'pt' ? 'pendentes' : 'pending' }}
    <span v-if="store.inboxError" style="margin-left: 10px; color: var(--danger, #c0392b)">
      · {{ store.inboxError }}
    </span>
  </div>

  <div class="page-body">
    <div class="list">
      <div class="list-row inbox-row no-hover" style="background: transparent; cursor: default; border-bottom-color: var(--border-strong)">
        <div class="h-eyebrow">{{ t('inbox_source') }}</div>
        <div class="h-eyebrow">{{ lang === 'pt' ? 'Texto' : 'Text' }}</div>
        <div class="h-eyebrow">{{ lang === 'pt' ? 'Autor' : 'Author' }}</div>
        <div class="h-eyebrow" style="text-align: right">{{ lang === 'pt' ? 'Status' : 'Status' }}</div>
      </div>

      <div v-if="inboxSkeletonVisible && !store.inboxIdeas.length" v-for="item in 6" :key="`skeleton-${item}`" class="list-row inbox-row no-hover inbox-row-skeleton skeleton-surface" aria-hidden="true">
        <Skeleton variant="badge" width="68px" height="24px" />
        <div><Skeleton variant="title" width="58%" height="16px" /><Skeleton variant="text" :lines="2" height="12px" /></div>
        <div><Skeleton variant="text" width="84%" height="12px" /><Skeleton variant="text" width="46%" height="10px" /></div>
        <Skeleton variant="badge" width="64px" height="24px" />
      </div>

      <div v-else v-for="idea in visibleList" :key="idea.id" class="list-row inbox-row no-hover">
        <SourcePill :source="idea.source" :lang="lang" />
        <div>
          <div v-if="idea.title" style="font-weight: 540; font-size: 13px; margin-bottom: 2px">
            {{ idea.title }}
          </div>
          <div class="line-clamp-2" style="font-size: 13.5px; line-height: 1.45">
            {{ idea.description }}
          </div>
        </div>
        <div class="mono truncate" style="font-size: 11.5px; color: var(--ink-3)">
          {{ idea.author_email || '—' }}
          <div v-if="idea.created_at" style="font-size: 10.5px; color: var(--ink-4)">
            {{ formatDate(idea.created_at) }}
          </div>
        </div>
        <div style="text-align: right">
          <template v-if="idea.clustered">
            <span class="pill">
              <span class="dot" style="background: var(--gold)" />
              <span class="truncate" style="max-width: 96px">{{ lang === 'pt' ? 'no roadmap' : 'on roadmap' }}</span>
            </span>
          </template>
          <template v-else>
            <span class="pill" style="background: transparent; color: var(--ink-4)">
              {{ lang === 'pt' ? 'pendente' : 'pending' }}
            </span>
          </template>
        </div>
      </div>

      <div v-if="!store.inboxLoading && visibleList.length === 0"
           style="padding: 40px 0; text-align: center; color: var(--ink-3)">
        {{ t('empty_state') }}
      </div>
    </div>
  </div>

  <CreateIdeaModal v-if="showCreate" @close="showCreate = false" />
</template>
