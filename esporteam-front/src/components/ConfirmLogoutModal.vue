<script setup>
import { computed } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'

defineEmits(['confirm', 'close'])
const store = useAppStore()
const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)
</script>

<template>
  <div class="modal-back open" @click.self="$emit('close')">
    <div class="modal" @keydown.esc="$emit('close')" tabindex="-1">
      <h2>{{ t('logout_confirm_title') }}</h2>
      <p class="sub">{{ t('logout_confirm_body') }}</p>

      <div class="row">
        <button type="button" class="btn" @click="$emit('close')">
          {{ lang === 'pt' ? 'Cancelar' : 'Cancel' }}
        </button>
        <button type="button" class="btn btn-primary" @click="$emit('confirm')">
          {{ t('logout_confirm_yes') }}
        </button>
      </div>
    </div>
  </div>
</template>
