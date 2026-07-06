<script setup>
import { ref, computed } from 'vue'
import { useAppStore } from '../stores/app'
import { STR, pickLang } from '../mock/i18n'
import Icon from './Icon.vue'

const store = useAppStore()
const email = ref('eduardo@mesa.app')
const password = ref('demo1234')

const lang = computed(() => store.lang)
const t = (k) => pickLang(STR[k], lang.value)

function submit() {
  store.login(email.value, password.value)
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

      <h1>{{ t('login_title') }}</h1>
      <p>{{ t('login_subtitle') }}</p>

      <label>{{ t('login_email') }}</label>
      <input class="input" type="email" v-model="email" autofocus />
      <label>{{ t('login_password') }}</label>
      <input class="input" type="password" v-model="password" />

      <div v-if="store.loginError" style="margin-top: 12px; color: var(--danger, #c0392b); font-size: 12px">
        {{ store.loginError }}
      </div>

      <div style="margin-top: 18px">
        <button class="btn btn-primary" type="submit" :disabled="store.loginLoading">
          {{ store.loginLoading ? '...' : t('login_submit') }} <Icon name="chevron" />
        </button>
      </div>

      <div class="auth-switch">
        <span>{{ t('login_no_account') }}</span>
        <button type="button" class="link" @click="store.setAuthView('register')">
          {{ t('login_to_register') }}
        </button>
      </div>
    </form>
  </div>
</template>
