<script setup>
import { ref, watch } from 'vue'
import { openEventConversation, postEventConversationMessage } from '../services/api'
import { subscribeToEventConversation } from '../services/eventConversationRealtime'

const props = defineProps({ sessionId: { type: [String, Number], default: null } })
const messages = ref([])
const text = ref('')
const loading = ref(false)
const sending = ref(false)
const error = ref('')
let unsubscribe = null

function merge(items = []) {
  const known = new Map(messages.value.map(message => [String(message.id), message]))
  items.forEach(message => known.set(String(message.id), message))
  messages.value = [...known.values()].sort((a, b) => Number(a.id) - Number(b.id))
}

async function load() {
  if (!props.sessionId) return
  loading.value = true; error.value = ''
  try {
    const conversation = await openEventConversation(props.sessionId)
    messages.value = []
    merge(conversation.messages)
    unsubscribe?.()
    unsubscribe = subscribeToEventConversation(conversation.conversation.id, message => merge([message]))
  } catch (err) {
    // A flag desligada ou uma sessão ainda sem conversa não prejudica o detalhe.
    if (err?.response?.status !== 404) error.value = 'Não foi possível carregar a conversa.'
  } finally { loading.value = false }
}

async function send() {
  const body = text.value.trim()
  if (!body || sending.value || !props.sessionId) return
  sending.value = true; error.value = ''
  try {
    const message = await postEventConversationMessage(props.sessionId, {
      body,
      clientMessageId: crypto.randomUUID(),
    })
    merge([message]); text.value = ''
  } catch { error.value = 'Mensagem não enviada. Tente novamente.' }
  finally { sending.value = false }
}

watch(() => props.sessionId, load, { immediate: true })
</script>

<template>
  <section v-if="sessionId" class="event-conversation" aria-label="Conversa da Sessão Esportiva">
    <h3>Conversa</h3>
    <p v-if="loading" class="event-conversation-muted">Carregando conversa…</p>
    <p v-else-if="!messages.length" class="event-conversation-muted">Seja a primeira pessoa a combinar os detalhes.</p>
    <ol v-else class="event-conversation-messages">
      <li v-for="message in messages" :key="message.id"><strong>{{ message.author?.display_name || 'Perfil Esportivo' }}</strong><span>{{ message.body }}</span></li>
    </ol>
    <form class="event-conversation-composer" @submit.prevent="send">
      <label class="sr-only" for="event-conversation-body">Mensagem</label>
      <textarea id="event-conversation-body" v-model="text" maxlength="2000" rows="2" placeholder="Escreva uma mensagem" :disabled="sending" />
      <button type="submit" :disabled="sending || !text.trim()">{{ sending ? 'Enviando…' : 'Enviar' }}</button>
    </form>
    <p v-if="error" class="event-conversation-error" role="alert">{{ error }}</p>
  </section>
</template>

<style scoped>
.event-conversation { border-top: 1px solid #e7e2d8; margin-top: 20px; padding-top: 18px; }
.event-conversation h3 { margin: 0 0 10px; font-size: 16px; }
.event-conversation-muted { color: #6c675e; font-size: 13px; }
.event-conversation-messages { display: grid; gap: 9px; list-style: none; margin: 0 0 12px; padding: 0; }
.event-conversation-messages li { display: grid; gap: 2px; border-radius: 10px; background: #f6f3ed; padding: 9px 11px; font-size: 13px; }
.event-conversation-messages strong { font-size: 12px; }
.event-conversation-composer { display: grid; gap: 8px; }
.event-conversation-composer textarea { resize: vertical; font: inherit; padding: 9px; }
.event-conversation-composer button { justify-self: end; }
.event-conversation-error { color: #b42318; font-size: 13px; }
</style>
