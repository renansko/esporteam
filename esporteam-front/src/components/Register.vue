<script setup>
import { ref } from 'vue'
import { useAppStore } from '../stores/app'
import Icon from './Icon.vue'
import { firstValidationError, isValidField } from '../services/validation'

const store = useAppStore()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const city = ref('')
const region = ref('')
const submitted = ref(false)
const touched = ref({})

const fieldError = (key) => {
  return firstValidationError(store.registerErrors, key)
}

function touch(key) {
  touched.value = { ...touched.value, [key]: true }
}

function fieldClass(key, value, options = {}) {
  if (fieldError(key)) return 'is-invalid'
  if ((submitted.value || touched.value[key]) && !isValidField(value, options)) return 'is-invalid'
  if ((submitted.value || touched.value[key]) && isValidField(value, options)) return 'is-valid'
  return ''
}

function fieldMessage(key, value, options = {}, message) {
  return fieldError(key) || (((submitted.value || touched.value[key]) && !isValidField(value, options)) ? message : null)
}

function clearError(key) {
  store.clearRegisterFieldError(key)
}

function submit() {
  submitted.value = true
  ;['name', 'email', 'password', 'password_confirmation'].forEach(touch)
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
      <input id="register-name" :class="['input', fieldClass('name', name, { required: true })]" type="text" v-model="name" autocomplete="name" autofocus required @blur="touch('name')" @input="clearError('name')" />
      <div v-if="fieldMessage('name', name, { required: true }, 'Informe seu nome.')" class="field-error">{{ fieldMessage('name', name, { required: true }, 'Informe seu nome.') }}</div>

      <label for="register-email">Email</label>
      <input id="register-email" :class="['input', fieldClass('email', email, { type: 'email', required: true })]" type="email" v-model="email" autocomplete="email" required @blur="touch('email')" @input="clearError('email')" />
      <div v-if="fieldMessage('email', email, { type: 'email', required: true }, 'Informe um e-mail válido.')" class="field-error">{{ fieldMessage('email', email, { type: 'email', required: true }, 'Informe um e-mail válido.') }}</div>

      <label for="register-password">Senha</label>
      <input id="register-password" :class="['input', fieldClass('password', password, { required: true })]" type="password" v-model="password" autocomplete="new-password" required @blur="touch('password')" @input="clearError('password')" />
      <div class="field-hint">Minimo 8 caracteres, com maiuscula, minuscula e numero.</div>
      <div v-if="fieldMessage('password', password, { required: true }, 'Informe uma senha.')" class="field-error">{{ fieldMessage('password', password, { required: true }, 'Informe uma senha.') }}</div>

      <label for="register-password-confirm">Confirmar senha</label>
      <input id="register-password-confirm" :class="['input', fieldClass('password_confirmation', passwordConfirmation, { required: true })]" type="password" v-model="passwordConfirmation" autocomplete="new-password" required @blur="touch('password_confirmation')" @input="clearError('password_confirmation')" />
      <div v-if="fieldMessage('password_confirmation', passwordConfirmation, { required: true }, 'Confirme sua senha.')" class="field-error">{{ fieldMessage('password_confirmation', passwordConfirmation, { required: true }, 'Confirme sua senha.') }}</div>

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
