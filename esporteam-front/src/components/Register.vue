<script setup>
import { ref, computed } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'
import Icon from './Icon.vue'

const store = useAppStore()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const workspaceName = ref('')

const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

const fieldError = (key) => {
  const errs = store.registerErrors?.[key]
  return Array.isArray(errs) ? errs[0] : errs || null
}

function submit() {
  store.register({
    name: name.value.trim(),
    email: email.value.trim(),
    password: password.value,
    passwordConfirmation: passwordConfirmation.value,
    workspaceName: workspaceName.value.trim(),
  })
}
</script>

<template>
  <div class="login-page">
    <form class="login-card" @submit.prevent="submit">
      <div class="login-logo">
        <div class="mark">P</div>
        <div>
          <div class="nm">Esporteam</div>
          <div :style="{ fontSize: '11px', color: 'var(--ink-3)', fontFamily: 'var(--font-mono)' }">
            {{ pickLang(STR.workspaceTagline, lang) }}
          </div>
        </div>
        <div style="margin-left: auto">
          <div class="chip-group">
            <div :class="['chip', { active: lang === 'pt' }]" @click="store.setLang('pt')">PT</div>
            <div :class="['chip', { active: lang === 'en' }]" @click="store.setLang('en')">EN</div>
          </div>
        </div>
      </div>

      <h1>{{ t('register_title') }}</h1>
      <p>{{ t('register_subtitle') }}</p>

      <label>{{ t('register_name') }}</label>
      <input class="input" type="text" v-model="name" autocomplete="name" autofocus required />
      <div v-if="fieldError('name')" class="field-error">{{ fieldError('name') }}</div>

      <label>{{ t('register_email') }}</label>
      <input class="input" type="email" v-model="email" autocomplete="email" required />
      <div v-if="fieldError('email')" class="field-error">{{ fieldError('email') }}</div>

      <label>{{ t('register_password') }}</label>
      <input class="input" type="password" v-model="password" autocomplete="new-password" required />
      <div v-if="fieldError('password')" class="field-error">{{ fieldError('password') }}</div>

      <label>{{ t('register_password_confirm') }}</label>
      <input class="input" type="password" v-model="passwordConfirmation" autocomplete="new-password" required />

      <label>{{ t('register_workspace') }}</label>
      <input class="input" type="text" v-model="workspaceName" required />
      <div class="field-hint">{{ t('register_workspace_hint') }}</div>

      <div v-if="store.registerError && !store.registerErrors" class="field-error" style="margin-top: 12px">
        {{ store.registerError }}
      </div>

      <div style="margin-top: 18px">
        <button class="btn btn-primary" type="submit" :disabled="store.registerLoading">
          {{ store.registerLoading ? '...' : t('register_submit') }} <Icon name="chevron" />
        </button>
      </div>

      <div class="auth-switch">
        <span>{{ t('register_have_account') }}</span>
        <button type="button" class="link" @click="store.setAuthView('login')">
          {{ t('register_to_login') }}
        </button>
      </div>
    </form>
  </div>
</template>
