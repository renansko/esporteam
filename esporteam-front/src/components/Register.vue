<script setup>
import { inject, ref } from 'vue'
import { useAppStore } from '../stores/app'
import { routerKey } from 'vue-router'
import Icon from './Icon.vue'
import UiButton from './ui/UiButton.vue'
import { firstValidationError, isValidField } from '../services/validation'

const store = useAppStore()
const router = inject(routerKey, null)
const step = ref('intent')
const intent = ref(null)
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const city = ref('')
const region = ref('')
const birthDate = ref('')
const adultAttestation = ref(false)
const submitted = ref(false)
const touched = ref({})

const fieldError = (key) => firstValidationError(store.registerErrors, key)

function selectIntent(value) {
  intent.value = value
  step.value = 'details'
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

function openLogin() {
  if (router) router.push('/entrar')
  else store.setAuthView('login')
}

function submit() {
  submitted.value = true
  ;['name', 'email', 'password', 'password_confirmation', 'birth_date', 'adult_attestation'].forEach(touch)
  if (!isValidField(name.value, { required: true }) || !isValidField(email.value, { type: 'email', required: true }) || !isValidField(password.value, { required: true }) || !isValidField(passwordConfirmation.value, { required: true }) || !birthDate.value || !adultAttestation.value) return

  store.register({
    name: name.value.trim(),
    email: email.value.trim(),
    password: password.value,
    passwordConfirmation: passwordConfirmation.value,
    city: city.value.trim(),
    region: region.value.trim(),
    intent: intent.value,
    birthDate: birthDate.value,
    adultAttestation: adultAttestation.value,
  })
}
</script>

<template>
  <div class="auth-page">
    <section class="auth-shell auth-shell-register" aria-labelledby="register-title">
      <div class="auth-preview" aria-hidden="true">
        <div class="auth-phone">
          <div class="auth-phone-bar"><span>9:41</span><span class="auth-phone-dots"><span></span><span></span><span></span></span></div>
          <div class="auth-profile-panel">
            <div class="auth-avatar">PE</div>
            <p>{{ intent === 'teacher' ? 'Perfil de Professor' : 'Perfil Esportivo' }}</p>
            <strong>{{ name || 'Seu nome' }}</strong>
            <span>{{ city || 'Cidade' }}{{ region ? `, ${region}` : '' }}</span>
          </div>
          <div class="auth-profile-list">
            <div><span>Modalidades</span><strong>Definir depois</strong></div>
            <div><span>{{ intent === 'teacher' ? 'Atuação' : 'Disponibilidade' }}</span><strong>{{ intent === 'teacher' ? 'Configurar como professor' : 'Alimenta a Descoberta' }}</strong></div>
          </div>
        </div>
      </div>

      <Transition name="motion-auth" mode="out-in">
      <section v-if="step === 'intent'" key="intent" class="auth-card auth-choice-card" aria-labelledby="register-title">
        <div class="auth-brand"><div class="auth-mark">C</div><div><div class="auth-name">Cola Aí</div><div class="auth-tagline">Descoberta esportiva local</div></div></div>
        <p class="auth-wizard-progress">Etapa 1 de 2</p>
        <p class="auth-eyebrow">Antes de começar</p>
        <h1 id="register-title">Você quer marcar um esporte ou participar?</h1>
        <p class="auth-lede">Isso define a experiência inicial. Você poderá complementar o seu Perfil Esportivo depois.</p>
        <div class="auth-intent-options" role="group" aria-label="Objetivo no Cola Aí">
          <button type="button" class="auth-intent-option" @click="selectIntent('teacher')">
            <span class="auth-intent-icon"><Icon name="calendarCheck" :size="22" /></span>
            <span><strong>Quero marcar um esporte</strong><small>Você cria práticas e começa como Professor.</small></span>
            <Icon name="chevron" :size="18" />
          </button>
          <button type="button" class="auth-intent-option" @click="selectIntent('participant')">
            <span class="auth-intent-icon"><Icon name="cards" :size="22" /></span>
            <span><strong>Quero participar</strong><small>Você encontra práticas para entrar perto de você.</small></span>
            <Icon name="chevron" :size="18" />
          </button>
        </div>
        <div class="auth-switch"><span>Já tem conta?</span><button type="button" class="link" @click="openLogin">Entrar</button></div>
      </section>

      <form v-else key="details" class="auth-card" @submit.prevent="submit">
        <button class="auth-wizard-back" type="button" @click="step = 'intent'"><Icon name="back" :size="16" /> Voltar</button>
        <div class="auth-brand"><div class="auth-mark">C</div><div><div class="auth-name">Cola Aí</div><div class="auth-tagline">Conta + Perfil Esportivo</div></div></div>
        <p class="auth-wizard-progress">Etapa 2 de 2</p>
        <p class="auth-eyebrow">{{ intent === 'teacher' ? 'Perfil de Professor' : 'Criar conta' }}</p>
        <h1 id="register-title">{{ intent === 'teacher' ? 'Prepare seu Perfil de Professor' : 'Comece pelo seu Perfil Esportivo' }}</h1>
        <p class="auth-lede">{{ intent === 'teacher' ? 'Criamos sua conta, seu Perfil Esportivo e as configurações iniciais de Professor.' : 'Criamos sua conta e um Perfil Esportivo público mínimo para a Descoberta.' }}</p>

        <label for="register-name">Nome</label>
        <input id="register-name" :class="['input', fieldClass('name', name, { required: true })]" type="text" v-model="name" autocomplete="name" autofocus required @blur="touch('name')" @input="clearError('name')" />
        <Transition name="motion-inline"><div v-if="fieldMessage('name', name, { required: true }, 'Informe seu nome.')" class="field-error">{{ fieldMessage('name', name, { required: true }, 'Informe seu nome.') }}</div></Transition>

        <label for="register-email">E-mail</label>
        <input id="register-email" :class="['input', fieldClass('email', email, { type: 'email', required: true })]" type="email" v-model="email" autocomplete="email" required @blur="touch('email')" @input="clearError('email')" />
        <Transition name="motion-inline"><div v-if="fieldMessage('email', email, { type: 'email', required: true }, 'Informe um e-mail válido.')" class="field-error">{{ fieldMessage('email', email, { type: 'email', required: true }, 'Informe um e-mail válido.') }}</div></Transition>

        <label for="register-password">Senha</label>
        <input id="register-password" :class="['input', fieldClass('password', password, { required: true })]" type="password" v-model="password" autocomplete="new-password" required @blur="touch('password')" @input="clearError('password')" />
        <div class="field-hint">Mínimo de 8 caracteres, com maiúscula, minúscula e número.</div>
        <Transition name="motion-inline"><div v-if="fieldMessage('password', password, { required: true }, 'Informe uma senha.')" class="field-error">{{ fieldMessage('password', password, { required: true }, 'Informe uma senha.') }}</div></Transition>

        <label for="register-password-confirm">Confirmar senha</label>
        <input id="register-password-confirm" :class="['input', fieldClass('password_confirmation', passwordConfirmation, { required: true })]" type="password" v-model="passwordConfirmation" autocomplete="new-password" required @blur="touch('password_confirmation')" @input="clearError('password_confirmation')" />
        <Transition name="motion-inline"><div v-if="fieldMessage('password_confirmation', passwordConfirmation, { required: true }, 'Confirme sua senha.')" class="field-error">{{ fieldMessage('password_confirmation', passwordConfirmation, { required: true }, 'Confirme sua senha.') }}</div></Transition>

        <div class="auth-grid"><div><label for="register-city">Cidade</label><input id="register-city" class="input" type="text" v-model="city" autocomplete="address-level2" /></div><div><label for="register-region">UF/região</label><input id="register-region" class="input" type="text" v-model="region" autocomplete="address-level1" /></div></div>
        <div class="field-hint">Localização aproximada; não usamos endereço residencial preciso.</div>
        <label for="register-birth-date">Data de nascimento</label>
        <input id="register-birth-date" :class="['input', fieldClass('birth_date', birthDate, { required: true })]" type="date" v-model="birthDate" required @blur="touch('birth_date')" @input="clearError('birth_date')" />
        <Transition name="motion-inline"><div v-if="fieldMessage('birth_date', birthDate, { required: true }, 'Informe sua data de nascimento.')" class="field-error">{{ fieldMessage('birth_date', birthDate, { required: true }, 'Informe sua data de nascimento.') }}</div></Transition>
        <label class="auth-attestation"><input type="checkbox" v-model="adultAttestation" required @change="touch('adult_attestation'); clearError('adult_attestation')" /> Confirmo que tenho 18 anos ou mais.</label>
        <Transition name="motion-inline"><div v-if="fieldError('adult_attestation') || (submitted && !adultAttestation)" class="field-error">{{ fieldError('adult_attestation') || 'Confirme sua maioridade para continuar.' }}</div></Transition>
        <Transition name="motion-inline"><div v-if="store.registerError && !store.registerErrors" class="field-error auth-error">{{ store.registerError }}</div></Transition>
        <UiButton class="btn btn-primary auth-submit" type="submit" variant="primary" :busy="store.registerLoading">{{ store.registerLoading ? 'Criando...' : intent === 'teacher' ? 'Criar conta de Professor' : 'Criar conta' }}<Icon name="chevron" /></UiButton>
        <div class="auth-switch"><span>Já tem conta?</span><button type="button" class="link" @click="openLogin">Entrar</button></div>
      </form>
      </Transition>
    </section>
  </div>
</template>
