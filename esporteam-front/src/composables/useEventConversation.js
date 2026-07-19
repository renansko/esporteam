import { ref } from 'vue'
import { applyEventConversationSocialAction, openEventConversation, postEventConversationMessage } from '../services/api'
import { subscribeToEventConversation } from '../services/eventConversationRealtime'

export function useEventConversation(sessionId) {
  const messages = ref([]); const text = ref(''); const loading = ref(false); const sending = ref(false); const error = ref('')
  const muted = ref(false); const typingProfileId = ref(null); let unsubscribe = null; let typingTimeout = null
  const merge = (items = []) => {
    const known = new Map(messages.value.map(message => [String(message.id), message]))
    items.forEach(message => known.set(String(message.id), message))
    messages.value = [...known.values()].sort((a, b) => Number(a.id) - Number(b.id))
  }
  const action = async payload => applyEventConversationSocialAction(sessionId.value, payload)
  const load = async () => {
    if (!sessionId.value) return
    loading.value = true; error.value = ''
    try {
      const conversation = await openEventConversation(sessionId.value); messages.value = []; merge(conversation.messages); muted.value = conversation.conversation.muted
      unsubscribe?.(); unsubscribe = subscribeToEventConversation(conversation.conversation.id, {
        onMessage: async message => { merge([message]); await action({ action: 'read', cursor: message.id }) },
        onSocial: state => {
          if (state.kind === 'message') merge([state.message])
          if (state.kind === 'typing') {
            typingProfileId.value = state.active ? state.profile_id : null
            clearTimeout(typingTimeout)
            if (state.active) typingTimeout = setTimeout(() => { typingProfileId.value = null }, 5000)
          }
        },
      })
      const cursor = messages.value.at(-1)?.id
      if (cursor) await action({ action: 'read', cursor })
    } catch (err) { if (err?.response?.status !== 404) error.value = 'Não foi possível carregar a conversa.' }
    finally { loading.value = false }
  }
  const send = async () => {
    const body = text.value.trim(); if (!body || sending.value || !sessionId.value) return
    sending.value = true; error.value = ''
    try { merge([await postEventConversationMessage(sessionId.value, { body, clientMessageId: crypto.randomUUID() })]); text.value = '' }
    catch { error.value = 'Mensagem não enviada. Tente novamente.' } finally { sending.value = false }
  }
  const reply = async (messageId, body) => merge([(await action({ action: 'reply', message_id: messageId, body, client_message_id: crypto.randomUUID() })).message])
  const react = async (messageId, emoji) => {
    const message = messages.value.find(item => Number(item.id) === Number(messageId))
    const active = !message?.reactions?.find(item => item.emoji === emoji)?.reacted
    merge([(await action({ action: 'reaction', message_id: messageId, emoji, active })).message])
  }
  const mention = async (messageId, profileId) => merge([(await action({ action: 'mention', message_id: messageId, mentioned_profile_id: profileId })).message])
  const setMuted = async value => { muted.value = (await action({ action: 'mute', muted: value })).muted }
  const typing = active => action({ action: 'typing', active }).catch(() => {})
  return { messages, text, loading, sending, error, muted, typingProfileId, load, send, reply, react, mention, setMuted, typing }
}
