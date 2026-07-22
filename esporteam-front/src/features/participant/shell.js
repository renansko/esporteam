export const DEFAULT_PARTICIPANT_TAB = 'map'

export const PARTICIPANT_TABS = [
  {
    id: 'map',
    label: 'Mapa',
    icon: 'map',
    eyebrow: 'Proximidade',
    title: 'Sessoes proximas',
    emptyState: {
      title: 'Mapa e Lista preparados',
      description: 'Mapa e Lista usam a mesma colecao de Sessoes Esportivas proximas para manter a Descoberta acessivel.',
    },
  },
  {
    id: 'discover',
    label: 'Descobrir',
    icon: 'cards',
    eyebrow: 'Descoberta',
    title: 'Sessoes Esportivas para voce',
    emptyState: {
      title: 'Descoberta pronta',
      description: 'Este espaco recebe cards de Sessao Esportiva com Modalidade, Anfitriao da Sessao, Nivel Esportivo e proxima acao.',
    },
  },
  {
    id: 'matches',
    label: 'Eventos',
    icon: 'calendarCheck',
    eyebrow: 'Agenda',
    title: 'Proximos eventos',
    emptyState: {
      title: 'Nenhum evento confirmado',
      description: 'Quando uma participacao for confirmada, ela aparece aqui como sua agenda esportiva.',
    },
  },
  {
    id: 'profile',
    label: 'Perfil',
    icon: 'user',
    eyebrow: 'Perfil Esportivo',
    title: 'Seu Perfil Esportivo',
    emptyState: {
      title: 'Perfil Esportivo ativo',
      description: 'Modalidades, Nivel Esportivo, Objetivos Esportivos e Disponibilidade alimentam a Descoberta do Entusiasta.',
    },
  },
]

export const PARTICIPANT_TAB_IDS = PARTICIPANT_TABS.map(tab => tab.id)

export const PARTICIPANT_ROUTE_BY_TAB = Object.freeze({
  discover: 'discover',
  map: 'map',
  matches: 'events',
  profile: 'profile',
})

export const PARTICIPANT_TAB_BY_ROUTE = Object.freeze(
  Object.fromEntries(Object.entries(PARTICIPANT_ROUTE_BY_TAB).map(([tab, route]) => [route, tab])),
)

export function isParticipantTab(tabId) {
  return PARTICIPANT_TAB_IDS.includes(tabId)
}

export function resolveParticipantTab(tabId) {
  return PARTICIPANT_TABS.find(tab => tab.id === tabId) || PARTICIPANT_TABS[0]
}

export function safeParticipantReturnPath(value, fallback = '/descobrir') {
  return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//')
    ? value
    : fallback
}

export function resolveSessionBackAction(historyState = {}) {
  return typeof historyState.returnTo === 'string'
    ? { type: 'back' }
    : { type: 'replace', to: '/descobrir' }
}
