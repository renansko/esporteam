import { computed, ref } from 'vue'
import {
  cancelSportSessionOccurrence,
  updateSportSessionOccurrence,
  updateSportSessionSeriesFromOccurrence,
} from '../services/sportDiscovery.js'
import { apiErrorMessage } from '../services/validation.js'

const occurrenceFields = ['version', 'title', 'description', 'rules', 'equipment', 'starts_at', 'ends_at', 'timezone', 'meeting_point_label', 'location_label_public', 'city', 'region', 'latitude', 'longitude', 'capacity', 'entry_mode', 'visibility', 'min_level', 'max_level']
const futureFields = [...occurrenceFields, 'series_version', 'starts_at_local', 'interval_weeks', 'weekdays', 'ends_type', 'ends_on', 'occurrence_count']

function editPayload(draft, scope) {
  const fields = scope === 'future' ? futureFields : occurrenceFields
  return Object.fromEntries(fields.filter(field => draft[field] !== undefined).map(field => [field, draft[field]]))
}

export function useOccurrenceManagement({ updateOne = updateSportSessionOccurrence, updateFuture = updateSportSessionSeriesFromOccurrence, cancel = cancelSportSessionOccurrence } = {}) {
  const draft = ref(null)
  const scope = ref('one')
  const loading = ref(false)
  const error = ref(null)
  const impact = ref({ occurrences: 1, participants: 0 })
  const canSubmit = computed(() => Boolean(draft.value?.id && draft.value?.version))

  function begin(session, nextImpact = {}) {
    draft.value = { ...session, series_version: session.series?.version }
    scope.value = 'one'
    impact.value = { occurrences: 1, participants: Number(session?.participant_count || 0), ...nextImpact }
    error.value = null
  }

  async function save() {
    if (!canSubmit.value) return null
    loading.value = true
    error.value = null
    try {
      const request = scope.value === 'future' ? updateFuture : updateOne
      return await request(draft.value.id, editPayload(draft.value, scope.value))
    } catch (err) {
      error.value = apiErrorMessage(err, 'Nao foi possivel salvar a alteracao. Seu preenchimento foi mantido para uma nova tentativa.')
      return null
    } finally {
      loading.value = false
    }
  }

  async function cancelOccurrence(reason = '') {
    if (!canSubmit.value) return null
    loading.value = true
    error.value = null
    try {
      return await cancel(draft.value.id, { version: draft.value.version, reason })
    } catch (err) {
      error.value = apiErrorMessage(err, 'Nao foi possivel cancelar a ocorrencia. Tente novamente.')
      return null
    } finally {
      loading.value = false
    }
  }

  return { draft, scope, loading, error, impact, canSubmit, begin, save, cancelOccurrence }
}
