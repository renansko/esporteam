<script setup>
import { ref } from 'vue'
import { useAppStore } from '../stores/app'
import Icon from './Icon.vue'

const store = useAppStore()
const email = ref('')
const password = ref('')

function submit() {
  store.login(email.value, password.value)
}
</script>

<template>
  <div class="auth-page">
    <section class="auth-shell" aria-labelledby="login-title">
      <div class="auth-preview" aria-hidden="true">
        <div class="auth-phone">
          <div class="auth-phone-bar">
            <span>9:41</span>
            <span class="auth-phone-dots">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </div>
          <div class="auth-phone-title">
            <strong>Descobrir</strong>
            <span><Icon name="filter" :size="18" /></span>
          </div>
          <div class="auth-session-card">
            <div class="auth-session-art auth-session-art-blue">
              <Icon name="cards" :size="58" />
              <span class="auth-badge auth-badge-curated">
                <Icon name="lock" :size="14" />
                Curadoria
              </span>
              <span class="auth-distance">1,2 km</span>
            </div>
            <div class="auth-session-copy">
              <strong>Volei de praia</strong>
              <span>com Prof. Marina</span>
              <div>
                <span>Sab, 9h</span>
                <span>Iniciante</span>
              </div>
            </div>
          </div>
          <div class="auth-action-row">
            <span>Pular</span>
            <span>Tenho interesse</span>
          </div>
        </div>
      </div>

      <form class="auth-card" @submit.prevent="submit">
        <div class="auth-brand">
          <div class="auth-mark">E</div>
          <div>
            <div class="auth-name">Esporteam</div>
            <div class="auth-tagline">Descoberta esportiva local</div>
          </div>
        </div>

        <p class="auth-eyebrow">Modo Participante</p>
        <h1 id="login-title">Entre para encontrar Sessoes Esportivas perto de voce</h1>
        <p class="auth-lede">
          Sua conta autentica o acesso. A Descoberta usa seu Perfil Esportivo.
        </p>

        <label for="login-email">Email</label>
        <input id="login-email" class="input" type="email" v-model="email" autocomplete="email" autofocus required />

        <label for="login-password">Senha</label>
        <input id="login-password" class="input" type="password" v-model="password" autocomplete="current-password" required />

        <div v-if="store.loginError" class="field-error auth-error">
          {{ store.loginError }}
        </div>

        <button class="btn btn-primary auth-submit" type="submit" :disabled="store.loginLoading">
          {{ store.loginLoading ? 'Entrando...' : 'Entrar' }}
          <Icon name="chevron" />
        </button>

        <div class="auth-switch">
          <span>Ainda nao tem conta?</span>
          <button type="button" class="link" @click="store.setAuthView('register')">
            Criar conta
          </button>
        </div>
      </form>
    </section>
  </div>
</template>
