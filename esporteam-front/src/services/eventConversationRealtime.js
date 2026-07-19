import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { loadToken } from './api'

let echo = null

function client() {
  if (echo) return echo
  window.Pusher = Pusher
  echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'esporteam-reverb-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth',
    auth: { headers: { Authorization: `Bearer ${loadToken() || ''}` } },
  })
  return echo
}

export function subscribeToEventConversation(conversationId, { onMessage, onSocial }) {
  const channelName = `event-conversations.${conversationId}`
  client().private(channelName).listen('.message.posted', onMessage)
    .listen('.social.updated', onSocial)
  return () => echo?.leave(channelName)
}
