import { getPushSettings, registerPushSubscription, removePushSubscription, setPushEnabled } from './api'

const DEVICE_KEY = 'esporteam.push.device'

function deviceId() {
  let id = localStorage.getItem(DEVICE_KEY)
  if (!id) { id = crypto.randomUUID(); localStorage.setItem(DEVICE_KEY, id) }
  return id
}

function decodeKey(value) {
  const padding = '='.repeat((4 - value.length % 4) % 4)
  const raw = atob((value + padding).replace(/-/g, '+').replace(/_/g, '/'))
  return Uint8Array.from(raw, char => char.charCodeAt(0))
}

export async function enablePushAfterExplicitAction() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || Notification.permission === 'denied') return false
  const settings = await getPushSettings()
  if (!settings?.public_key) return false
  const permission = Notification.permission === 'granted' ? 'granted' : await Notification.requestPermission()
  if (permission !== 'granted') { await setPushEnabled(false); return false }
  const registration = await navigator.serviceWorker.register('/sw.js')
  const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: decodeKey(settings.public_key) })
  await registerPushSubscription({ device_id: deviceId(), endpoint: subscription.endpoint, keys: subscription.toJSON().keys })
  await setPushEnabled(true)
  return true
}

export async function disablePush() {
  await removePushSubscription(localStorage.getItem(DEVICE_KEY))
  const registration = await navigator.serviceWorker.getRegistration('/sw.js')
  await registration?.pushManager.getSubscription().then(subscription => subscription?.unsubscribe())
  await setPushEnabled(false)
}
