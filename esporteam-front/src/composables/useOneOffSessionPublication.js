import { computed, ref } from 'vue'
import { publishOneOffSportSession } from '../services/sportDiscovery.js'
import { apiErrorMessage } from '../services/validation.js'

function newPublicationKey() {
  return globalThis.crypto?.randomUUID?.() ?? `session-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function localDateTime(hoursFromNow = 1) {
  const date = new Date(Date.now() + hoursFromNow * 60 * 60 * 1000)
  date.setMinutes(Math.ceil(date.getMinutes() / 15) * 15, 0, 0)
  const pad = value => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export function useOneOffSessionPublication({ publish = publishOneOffSportSession } = {}) {
  const open = ref(false)
  const step = ref(0)
  const loading = ref(false)
  const error = ref(null)
  const key = ref(newPublicationKey())
  const draft = ref({})
  const sports = ref([])
  const canReview = computed(() => Boolean(
    draft.value.sport_id && draft.value.title && draft.value.type && draft.value.starts_at && draft.value.ends_at
    && draft.value.timezone && draft.value.meeting_point_label && draft.value.location_label_public
    && draft.value.city && draft.value.region && draft.value.latitude !== '' && draft.value.longitude !== ''
    && draft.value.entry_mode && draft.value.visibility,
  ))

  function begin(profile = {}) {
    const rawSports = Array.isArray(profile.raw?.sports) ? profile.raw.sports : []
    sports.value = rawSports.map(item => ({
      id: item.sport_id ?? item.sport?.id ?? item.id,
      name: item.sport?.name ?? item.name ?? 'Modalidade',
    })).filter(item => item.id !== undefined && item.id !== null)
    const primarySport = sports.value[0]
    const primaryModality = profile.primaryModality || primarySport?.name || 'Sessão'
    draft.value = {
      sport_id: primarySport?.id ?? '', title: `${primaryModality} no mapa`, type: 'partida',
      starts_at: localDateTime(1), ends_at: localDateTime(2),
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Sao_Paulo',
      meeting_point_label: 'Ponto selecionado no mapa', location_label_public: profile.city || 'Local selecionado no mapa',
      city: profile.city || 'Local selecionado', region: profile.region || 'A definir',
      latitude: '', longitude: '', entry_mode: 'publica_direta', visibility: 'public', description: '', capacity: '',
    }
    key.value = newPublicationKey()
    step.value = 0
    error.value = null
    open.value = true
  }

  function close() { open.value = false }

  async function publishDraft() {
    if (!canReview.value) return null
    loading.value = true
    error.value = null
    try {
      const payload = { ...draft.value, capacity: draft.value.capacity === '' ? undefined : Number(draft.value.capacity) }
      const session = await publish(payload, { idempotencyKey: key.value })
      open.value = false
      return session
    } catch (err) {
      error.value = apiErrorMessage(err, 'Nao foi possivel publicar a Sessao Esportiva. Seu preenchimento foi mantido para uma nova tentativa.')
      return null
    } finally {
      loading.value = false
    }
  }

  return { open, step, loading, error, draft, sports, canReview, begin, close, publishDraft }
}
