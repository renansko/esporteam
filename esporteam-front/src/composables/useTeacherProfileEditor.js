import { computed, reactive, watch } from 'vue'

export function createTeacherProfileDraft(teacherProfile = null) {
  return {
    headline: teacherProfile?.headline || '',
    credentials: teacherProfile?.credentials || '',
    hourly_price_cents: teacherProfile?.hourly_price_cents ?? null,
    service_radius_km: teacherProfile?.service_radius_km ?? null,
  }
}

export function useTeacherProfileEditor(teacherProfile) {
  const draft = reactive(createTeacherProfileDraft(teacherProfile?.value || teacherProfile))
  const hourlyPrice = computed({
    get: () => draft.hourly_price_cents === null || draft.hourly_price_cents === undefined
      ? ''
      : draft.hourly_price_cents / 100,
    set: (value) => {
      const amount = Number(String(value).replace(',', '.'))
      draft.hourly_price_cents = Number.isFinite(amount) && amount >= 0 ? Math.round(amount * 100) : null
    },
  })

  watch(teacherProfile, next => Object.assign(draft, createTeacherProfileDraft(next)), { deep: true })

  return { draft, hourlyPrice }
}
