<script setup>
import { ref } from 'vue'
import { useAppStore } from '../stores/app'
import Icon from './Icon.vue'

const store = useAppStore()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const city = ref('')
const region = ref('')

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
    city: city.value.trim(),
    region: region.value.trim(),
  })
}
</script>

<template>
  <div class="auth-page">
    <section class="auth-shell auth-shell-register" aria-labelledby="register-title">
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
          <div class="auth-profile-panel">
            <div class="auth-avatar">PE</div>
            <p>Perfil Esportivo</p>
            <strong>{{ name || 'Seu nome' }}</strong>
            <span>{{ city || 'Cidade' }}{{ region ? `, ${region}` : '' }}</span>
          </div>
          <div class="auth-profile-list">
            <div>
              <span>Modalidades</span>
              <strong>Definir depois</strong>
            </div>
            <div>
              <span>Disponibilidade</span>
              <strong>Alimenta a Descoberta</strong>
            </div>
          </div>
        </div>
      </div>

      <form class="auth-card" @submit.prevent="submit">
        <div class="auth-brand">
          <div class="auth-mark">E</div>
          <div>
            <div class="auth-name">Esporteam</div>
            <div class="auth-tagline">Conta + Perfil Esportivo</div>
          </div>
        </div>

        <p class="auth-eyebrow">Criar conta</p>
        <h1 id="register-title">Comece pelo seu Perfil Esportivo</h1>
        <p class="auth-lede">
          Criamos o usuario no auth e um Perfil Esportivo publico minimo para a Descoberta.
        </p>

      <label for="register-name">Nome</label>
      <input id="register-name" class="input" type="text" v-model="name" autocomplete="name" autofocus required />
      <div v-if="fieldError('name')" class="field-error">{{ fieldError('name') }}</div>

      <label for="register-email">Email</label>
      <input id="register-email" class="input" type="email" v-model="email" autocomplete="email" required />
      <div v-if="fieldError('email')" class="field-error">{{ fieldError('email') }}</div>

      <label for="register-password">Senha</label>
      <input id="register-password" class="input" type="password" v-model="password" autocomplete="new-password" required />
      <div class="field-hint">Minimo 8 caracteres, com maiuscula, minuscula e numero.</div>
      <div v-if="fieldError('password')" class="field-error">{{ fieldError('password') }}</div>

      <label for="register-password-confirm">Confirmar senha</label>
      <input id="register-password-confirm" class="input" type="password" v-model="passwordConfirmation" autocomplete="new-password" required />

      <div class="auth-grid">
        <div>
          <label for="register-city">Cidade</label>
          <input id="register-city" class="input" type="text" v-model="city" autocomplete="address-level2" />
        </div>
        <div>
          <label for="register-region">UF/regiao</label>
          <input id="register-region" class="input" type="text" v-model="region" autocomplete="address-level1" />
        </div>
      </div>
      <div class="field-hint">Localizacao aproximada; nao usamos endereco residencial preciso.</div>

      <div v-if="store.registerError && !store.registerErrors" class="field-error" style="margin-top: 12px">
        {{ store.registerError }}
      </div>

      <button class="btn btn-primary auth-submit" type="submit" :disabled="store.registerLoading">
        {{ store.registerLoading ? 'Criando...' : 'Criar conta' }}
        <Icon name="chevron" />
      </button>

      <div class="auth-switch">
        <span>Ja tem conta?</span>
        <button type="button" class="link" @click="store.setAuthView('login')">
          Entrar
        </button>
      </div>
    </form>
    </section>
  </div>
</template>
