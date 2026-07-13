import { computed, ref } from 'vue'

const DISMISSAL_KEY_PREFIX = 'esporteam.bio-assistant.dismissed.'

function profileValue(profile) {
  return profile?.raw || profile || {}
}

export function normalizeBioAssistantOnboarding(profile) {
  const source = profileValue(profile)
  const onboarding = source.bio_assistant_onboarding || {}
  const completedAt = onboarding.completed_at || null
  const hasExistingBio = Boolean(String(source.bio || '').trim())

  return {
    // A persisted timestamp is authoritative. The bio fallback only prevents
    // existing profiles from receiving a needless first-run invitation before
    // they are backfilled by the API.
    completedAt: completedAt || (hasExistingBio ? source.updated_at || 'existing-bio' : null),
    eligible: Boolean(!completedAt && !hasExistingBio && onboarding.eligible !== false),
    blockingFields: Array.isArray(onboarding.blocking_fields) ? onboarding.blocking_fields : [],
  }
}

function defaultSessionStore() {
  if (typeof sessionStorage === 'undefined') return null
  return sessionStorage
}

export function useBioAssistantOnboarding({ sessionStore = defaultSessionStore() } = {}) {
  const open = ref(false)
  const automatic = ref(false)
  const onboarding = ref(normalizeBioAssistantOnboarding())
  const profileId = ref(null)

  const dismissalKey = computed(() => `${DISMISSAL_KEY_PREFIX}${profileId.value || 'pending'}`)
  const canInvite = computed(() => (
    onboarding.value.eligible
      && !onboarding.value.completedAt
      && !onboarding.value.blockingFields.length
      && sessionStore?.getItem(dismissalKey.value) !== '1'
  ))

  function evaluate(profile, { active = true } = {}) {
    const source = profileValue(profile)
    profileId.value = source.id || profile?.id || null
    onboarding.value = normalizeBioAssistantOnboarding(profile)
    if (active && canInvite.value && !open.value) {
      automatic.value = true
      open.value = true
      return true
    }
    return false
  }

  function showManual() {
    automatic.value = false
    open.value = true
  }

  function close() {
    open.value = false
    automatic.value = false
  }

  function dismissForSession() {
    sessionStore?.setItem(dismissalKey.value, '1')
    close()
  }

  function complete() {
    onboarding.value = {
      ...onboarding.value,
      eligible: false,
      blockingFields: [],
    }
    close()
  }

  return {
    open,
    automatic,
    onboarding,
    canInvite,
    evaluate,
    showManual,
    close,
    dismissForSession,
    complete,
  }
}
