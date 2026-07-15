<script setup>
import { ref } from 'vue'
import { useAppStore } from '../stores/app'

const store = useAppStore()
const birthDate = ref('')
const attested = ref(false)
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  if (!birthDate.value || !attested.value) {
    error.value = 'Informe sua data de nascimento e confirme que tem 18 anos ou mais.'
    return
  }

  loading.value = true
  try {
    await store.completeAdultEligibility({ birthDate: birthDate.value, adultAttestation: attested.value })
  } catch (cause) {
    error.value = cause?.response?.data?.errors?.birth_date?.[0] || 'Não foi possível concluir sua declaração agora.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page" role="dialog" aria-modal="true" aria-labelledby="adult-eligibility-title">
    <section class="auth-card">
      <p class="auth-eyebrow">Antes de participar</p>
      <h1 id="adult-eligibility-title">Confirme sua maioridade</h1>
      <p class="auth-lede">Para hospedar ou participar de uma Sessão Esportiva, você precisa ter 18 anos ou mais. A Descoberta continua disponível.</p>
      <label for="adult-birth-date">Data de nascimento</label>
      <input id="adult-birth-date" v-model="birthDate" class="input" type="date" required />
      <label class="auth-attestation"><input v-model="attested" type="checkbox" required /> Confirmo que tenho 18 anos ou mais.</label>
      <p v-if="error" class="field-error">{{ error }}</p>
      <button class="btn btn-primary auth-submit" type="button" :disabled="loading" @click="submit">{{ loading ? 'Confirmando...' : 'Concluir declaração' }}</button>
    </section>
  </div>
</template>
