<script setup>
import { ref, watch } from 'vue'
import { useEventConversation } from '../composables/useEventConversation'
import { enablePushAfterExplicitAction } from '../services/pushNotifications'

const props = defineProps({ sessionId: { type: [String, Number], default: null } })
const sessionId = ref(props.sessionId)
const chat = useEventConversation(sessionId)
const enablePush = async () => { try { await enablePushAfterExplicitAction() } catch {} }
watch(() => props.sessionId, value => { sessionId.value = value; chat.load() }, { immediate: true })
</script>

<template>
  <section v-if="sessionId" class="event-conversation" aria-label="Conversa da Sessão Esportiva">
    <header><h3>Conversa</h3><span><button type="button" class="event-conversation-mute" @click="enablePush">Ativar notificações</button> <button type="button" class="event-conversation-mute" @click="chat.setMuted(!chat.muted)">{{ chat.muted ? 'Ativar avisos' : 'Silenciar' }}</button></span></header>
    <p v-if="chat.loading" class="event-conversation-muted">Carregando conversa…</p>
    <p v-else-if="!chat.messages.length" class="event-conversation-muted">Seja a primeira pessoa a combinar os detalhes.</p>
    <ol v-else class="event-conversation-messages">
      <li v-for="message in chat.messages" :key="message.id">
        <strong>{{ message.author?.display_name || 'Perfil Esportivo' }}</strong>
        <small v-if="message.reply_to">Em resposta a {{ message.reply_to.author?.display_name || 'mensagem removida' }}: {{ message.reply_to.body || 'Mensagem removida' }}</small>
        <span>{{ message.body }}</span>
        <div v-if="message.media?.length" class="event-conversation-images"><img v-for="item in message.media" v-if="item.status === 'approved' && item.url" :key="item.id" :src="item.url" alt="Foto enviada na conversa" /><small v-else>Foto em processamento</small></div>
        <div class="event-conversation-actions"><button v-for="emoji in ['👍', '❤️', '😂']" :key="emoji" type="button" @click="chat.react(message.id, emoji)">{{ emoji }} {{ message.reactions?.find(item => item.emoji === emoji)?.count || '' }}</button></div>
        <small v-if="message.seen_by_count !== undefined">Visto por {{ message.seen_by_count }}</small>
      </li>
    </ol>
    <p v-if="chat.typingProfileId" class="event-conversation-muted">Alguém está digitando…</p>
    <form class="event-conversation-composer" @submit.prevent="chat.send">
      <label class="sr-only" for="event-conversation-body">Mensagem</label>
      <textarea id="event-conversation-body" v-model="chat.text" maxlength="2000" rows="2" placeholder="Escreva uma mensagem" :disabled="chat.sending" @input="chat.typing(true)" @blur="chat.typing(false)" />
      <label class="event-conversation-photos">Adicionar fotos <input type="file" accept="image/jpeg,image/png,image/webp" multiple @change="chat.addFiles($event.target.files); $event.target.value = ''" /></label>
      <ul v-if="chat.media.length" class="event-conversation-media"><li v-for="item in chat.media" :key="item.id">{{ item.name }} — {{ item.status === 'processing' ? 'Processando…' : item.status === 'approved' ? 'Pronta' : 'Não aprovada' }} <button type="button" @click="chat.removeMedia(item.id)">Remover</button></li></ul>
      <button type="submit" :disabled="chat.sending || (!chat.text.trim() && !chat.media.some(item => item.status === 'approved'))">{{ chat.sending ? 'Enviando…' : 'Enviar' }}</button>
    </form>
    <p v-if="chat.error" class="event-conversation-error" role="alert">{{ chat.error }}</p>
  </section>
</template>

<style scoped>
.event-conversation { border-top: 1px solid #e7e2d8; margin-top: 20px; padding-top: 18px; }
.event-conversation header { align-items: center; display: flex; justify-content: space-between; margin-bottom: 10px; }
.event-conversation h3 { margin: 0; font-size: 16px; }.event-conversation-mute { font: inherit; font-size: 12px; }
.event-conversation-muted { color: #6c675e; font-size: 13px; }.event-conversation-messages { display: grid; gap: 9px; list-style: none; margin: 0 0 12px; padding: 0; }
.event-conversation-messages li { display: grid; gap: 4px; border-radius: 10px; background: #f6f3ed; padding: 9px 11px; font-size: 13px; }.event-conversation-messages strong { font-size: 12px; }.event-conversation-messages small { color: #6c675e; }
.event-conversation-actions { display: flex; gap: 4px; }.event-conversation-actions button { font-size: 12px; }.event-conversation-composer { display: grid; gap: 8px; }.event-conversation-composer textarea { resize: vertical; font: inherit; padding: 9px; }.event-conversation-composer button { justify-self: end; }.event-conversation-error { color: #b42318; font-size: 13px; }
.event-conversation-photos { font-size: 13px; }.event-conversation-media { display: grid; gap: 4px; list-style: none; margin: 0; padding: 0; font-size: 12px; }.event-conversation-media button { margin-left: 6px; }
.event-conversation-images { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }.event-conversation-images img { width: 100%; border-radius: 8px; max-height: 220px; object-fit: cover; }
</style>
