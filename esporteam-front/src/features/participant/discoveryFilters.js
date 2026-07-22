export const DEFAULT_DISCOVERY_SESSION_FILTERS = Object.freeze({
  sportSlugs: [],
  sportSlug: '',
  level: '',
  goal: '',
  distanceKm: 10,
  weekday: '',
  startsAt: '',
  endsAt: '',
  participationType: 'all',
})

export const DISCOVERY_SPORT_OPTIONS = [
  { value: '', label: 'Todas' },
  { value: 'corrida', label: 'Corrida' },
  { value: 'volei', label: 'Volei' },
  { value: 'futebol', label: 'Futebol' },
  { value: 'beach-tennis', label: 'Beach tennis' },
]

export const DISCOVERY_LEVEL_OPTIONS = [
  { value: '', label: 'Todos' },
  { value: 'iniciante', label: 'Iniciante' },
  { value: 'intermediario', label: 'Intermediario' },
  { value: 'avancado', label: 'Avancado' },
]

export const DISCOVERY_GOAL_OPTIONS = [
  { value: '', label: 'Todos' },
  { value: 'casual', label: 'Casual' },
  { value: 'treino', label: 'Treino' },
  { value: 'aprender', label: 'Aprender' },
  { value: 'competir', label: 'Competir' },
]

export const DISCOVERY_WEEKDAY_OPTIONS = [
  { value: '', label: 'Qualquer dia' },
  { value: 'segunda', label: 'Segunda' },
  { value: 'terca', label: 'Terca' },
  { value: 'quarta', label: 'Quarta' },
  { value: 'quinta', label: 'Quinta' },
  { value: 'sexta', label: 'Sexta' },
  { value: 'sabado', label: 'Sabado' },
  { value: 'domingo', label: 'Domingo' },
]

export const DISCOVERY_PARTICIPATION_TYPE_OPTIONS = [
  { value: 'all', label: 'Todas' },
  { value: 'open', label: 'Aberta' },
  { value: 'curated', label: 'Curadoria' },
]

export function createDefaultDiscoverySessionFilters() {
  return { ...DEFAULT_DISCOVERY_SESSION_FILTERS, sportSlugs: [] }
}

export function discoveryFilterOptionLabel(options, value) {
  if (!value || value === 'all') return ''
  return options.find(option => option.value === value)?.label ?? value
}
