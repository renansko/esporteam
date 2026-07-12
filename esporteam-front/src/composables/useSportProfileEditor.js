import { reactive, ref, watch } from 'vue'
import { normalizeValidationErrors } from '../services/validation.js'

export function createSportProfileDraft(profile = {}) {
  const raw = profile.raw || profile
  const sports = Array.isArray(raw.sports) && raw.sports.length
    ? raw.sports
    : (profile.modalities || [])
      .filter(item => item?.id !== undefined && item?.id !== null)
      .map((item, index) => ({
        sport_id: item.id,
        sport: { id: item.id, name: item.name },
        level: item.level,
        goals: item.goal ? [item.goal] : [],
        is_primary: index === 0,
      }))
  const availability = Array.isArray(raw.availability) && raw.availability.length
    ? raw.availability
    : (profile.availability || []).map((label, index) => ({
      weekday: index % 7,
      starts_at: '08:00',
      ends_at: '10:00',
      label,
    }))

  return {
    profile: {
      display_name: profile.displayName || raw.display_name || '',
      bio: profile.bio || raw.bio || '',
      city: profile.city || raw.city || '',
      region: profile.region || raw.region || '',
      visibility: profile.visibility || raw.visibility || 'public',
    },
    sports: sports.map(item => ({
      sport_id: item.sport_id ?? item.sport?.id ?? item.id,
      name: item.sport?.name || item.name || 'Modalidade',
      level: item.level || '',
      goals: Array.isArray(item.goals) ? [...item.goals] : [],
      preferred_positions: item.preferred_positions || '',
      is_primary: Boolean(item.is_primary),
    })),
    availability: availability.map(item => ({
      weekday: Number(item.weekday),
      starts_at: item.starts_at || item.startsAt || '',
      ends_at: item.ends_at || item.endsAt || '',
    })),
  }
}

export function useSportProfileEditor(profile, { save = null, onSaved = () => {} } = {}) {
  const draft = reactive(createSportProfileDraft(profile?.value || profile))
  const loading = ref(false)
  const error = ref(null)
  const validationErrors = ref({})
  const success = ref(false)

  watch(profile, next => Object.assign(draft, createSportProfileDraft(next)), { deep: true })

  async function saveDraft() {
    loading.value = true
    error.value = null
    validationErrors.value = {}
    success.value = false
    try {
      const result = await save(draft)
      onSaved(result)
      success.value = true
      return result
    } catch (err) {
      validationErrors.value = normalizeValidationErrors(err)
      error.value = err?.response?.data?.message || err?.message || 'Nao foi possivel salvar o Perfil Esportivo.'
      return null
    } finally {
      loading.value = false
    }
  }

  return { draft, loading, error, validationErrors, success, saveDraft }
}
