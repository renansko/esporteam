export {
  DEFAULT_PARTICIPANT_TAB,
  PARTICIPANT_TABS,
  PARTICIPANT_TAB_IDS,
  isParticipantTab,
  resolveParticipantTab,
} from '../features/participant/shell.js'

export const MOCK_ACTIVE_SPORT_PROFILE = {
  id: 'sport-profile-demo',
  displayName: 'Renan Alves',
  role: 'Entusiasta',
  locationLabel: 'Florianopolis, SC',
  primaryModality: 'Corrida',
  modalities: [
    { name: 'Corrida', level: 'Iniciante', goal: 'Praticar com regularidade' },
    { name: 'Volei de praia', level: 'Iniciante', goal: 'Aprender fundamentos' },
  ],
  availability: ['Sabados pela manha', 'Domingos cedo', 'Terças a noite'],
}

export const MOCK_COMPATIBLE_SPORT_SESSIONS = [
  {
    id: 'discovery-card-corrida-parque',
    sport_profile_id: 'sport-profile-demo',
    distance_meters: 2400,
    score_label: 'Alta compatibilidade',
    next_action: 'show_interest',
    sport_session: {
      id: 'sport-session-corrida-parque',
      title: 'Corrida leve no parque',
      modality: { id: 'mod-corrida', name: 'Corrida' },
      host_sport_profile: {
        id: 'sport-profile-host-marina',
        display_name: 'Marina Costa',
        role: 'Organizador',
      },
      starts_at: '2026-07-12T08:00:00-03:00',
      location: {
        label: 'Parque de Coqueiros',
        city: 'Florianopolis',
        region: 'SC',
        latitude: -27.5969,
        longitude: -48.5482,
      },
      description: 'Ritmo leve para Entusiastas que querem voltar a correr com constancia.',
      meeting_point: 'Entrada principal do Parque de Coqueiros, perto do bicicletario.',
      rules: ['Chegar 10 minutos antes', 'Avisar o Anfitriao da Sessao se precisar sair antes'],
      equipment: ['Tenis de corrida', 'Garrafa de agua'],
      entry_mode: 'publica_direta',
      entry_rule: 'match_required',
      next_action: 'entrar',
      participation_status: null,
      level: 'Iniciante',
      participant_count: 8,
      approved_participants: [
        { id: 'sport-profile-mock-1', display_name: 'Ana Silva' },
        { id: 'sport-profile-mock-2', display_name: 'Bruno Lima' },
      ],
    },
  },
  {
    id: 'discovery-card-volei-curadoria',
    sport_profile_id: 'sport-profile-demo',
    distance_meters: 5100,
    score_label: 'Boa compatibilidade',
    next_action: 'request_participation',
    sport_session: {
      id: 'sport-session-volei-curadoria',
      title: 'Volei de praia tecnico',
      modality: { id: 'mod-volei-praia', name: 'Volei de praia' },
      host_sport_profile: {
        id: 'sport-profile-host-luiz',
        display_name: 'Luiz Pereira',
        role: 'Professor',
      },
      starts_at: '2026-07-13T19:00:00-03:00',
      location: {
        label: 'Beira-mar Norte',
        city: 'Florianopolis',
        region: 'SC',
        latitude: -27.5805,
        longitude: -48.5489,
      },
      entry_mode: 'curated',
      next_action: 'request_approval',
      participation_status: null,
      level: 'Iniciante',
      participant_count: 5,
    },
  },
]

export const MOCK_SPORT_SESSION_DETAILS = [
  {
    id: 'sport-session-corrida-parque',
    title: 'Corrida leve no parque',
    description: 'Ritmo leve para Entusiastas que querem voltar a correr com constancia.',
    sport: { id: 'mod-corrida', name: 'Corrida' },
    creator: {
      id: 'sport-profile-host-marina',
      display_name: 'Marina Costa',
      role: 'Organizador',
    },
    starts_at: '2026-07-12T08:00:00-03:00',
    location_label_public: 'Parque de Coqueiros',
    meeting_point: 'Entrada principal do Parque de Coqueiros, perto do bicicletario.',
    city: 'Florianopolis',
    region: 'SC',
    entry_mode: 'publica_direta',
    entry_rule: 'match_required',
    next_action: 'entrar',
    requires_approval: false,
    min_level: 'Iniciante',
    max_level: 'Iniciante',
    participant_count: 8,
    rules: ['Chegar 10 minutos antes', 'Avisar o Anfitriao da Sessao se precisar sair antes'],
    equipment: ['Tenis de corrida', 'Garrafa de agua'],
    participants: [
      { id: 'sport-profile-mock-1', display_name: 'Ana Silva' },
      { id: 'sport-profile-mock-2', display_name: 'Bruno Lima' },
    ],
  },
]
