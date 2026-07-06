<script setup>
import { ref, computed } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'

const emit = defineEmits(['close'])
const store = useAppStore()

const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const description = ref('')
const authorEmail = ref('')
const submitting = ref(false)
const error = ref(null)

const trimmed = computed(() => description.value.trim())
const canSubmit = computed(() => trimmed.value.length > 0 && !submitting.value)

async function submit() {
  if (!canSubmit.value) return
  submitting.value = true
  error.value = null
  try {
    await store.createInboxIdea({
      description: trimmed.value,
      authorEmail: authorEmail.value.trim() || undefined,
    })
    store.setToast({
      pt: 'Ideia adicionada à inbox.',
      en: 'Idea added to inbox.',
    })
    emit('close')
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors) {
      const first = Object.values(apiErrors)[0]
      error.value = Array.isArray(first) ? first[0] : String(first)
    } else {
      error.value = err?.response?.data?.message || err?.message || 'create_failed'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="modal-back open" @click.self="$emit('close')">
    <div class="modal" @keydown.esc="$emit('close')" tabindex="-1">
      <h2>{{ lang === 'pt' ? 'Nova ideia' : 'New idea' }}</h2>
      <p class="sub">
        {{ lang === 'pt'
            ? 'Entrada bruta — texto livre. A IA cuida do agrupamento depois.'
            : 'Raw input — free text. AI clusters later.' }}
      </p>

      <label class="h-eyebrow" style="display: block; margin-bottom: 6px">
        {{ lang === 'pt' ? 'Texto' : 'Text' }}
      </label>
      <textarea
        v-model="description"
        class="input"
        rows="5"
        :placeholder="lang === 'pt' ? 'Ex.: cliente pediu exportação em CSV…' : 'e.g. customer asked for CSV export…'"
        maxlength="5000"
        autofocus
        @keydown.meta.enter="submit"
        @keydown.ctrl.enter="submit"
      />

      <label class="h-eyebrow" style="display: block; margin: 12px 0 6px">
        {{ lang === 'pt' ? 'Email do autor (opcional)' : 'Author email (optional)' }}
      </label>
      <input
        v-model="authorEmail"
        class="input"
        type="email"
        placeholder="cliente@empresa.com"
      />

      <div v-if="error" style="margin-top: 10px; color: var(--danger, #c0392b); font-size: 12px">
        {{ error }}
      </div>

      <div class="row">
        <button type="button" class="btn" @click="$emit('close')" :disabled="submitting">
          {{ lang === 'pt' ? 'Cancelar' : 'Cancel' }}
        </button>
        <button type="button" class="btn btn-primary" :disabled="!canSubmit" @click="submit">
          {{ submitting ? '...' : (lang === 'pt' ? 'Adicionar' : 'Add') }}
        </button>
      </div>
    </div>
  </div>
</template>
