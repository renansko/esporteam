self.addEventListener('push', event => {
  let data = {}
  try { data = event.data?.json() || {} } catch { data = {} }
  const title = data.title || 'Cola Aí'
  event.waitUntil(self.registration.showNotification(title, {
    body: data.body || 'Há uma novidade na sua conversa.',
    data: data.data || {},
    tag: data.data?.conversation_id ? `conversation-${data.data.conversation_id}` : undefined,
  }))
})

self.addEventListener('notificationclick', event => {
  event.notification.close()
  const url = event.notification.data?.url || '/'
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windows => {
    const target = windows.find(window => 'focus' in window)
    return target ? target.focus().then(() => target.location.href = url) : clients.openWindow(url)
  }))
})
