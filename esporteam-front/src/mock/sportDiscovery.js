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

export const PARTICIPANT_TABS = [
  {
    id: 'discover',
    label: 'Descobrir',
    icon: 'cards',
    eyebrow: 'Descoberta',
    title: 'Sessoes Esportivas para voce',
    status: 'Pronto para receber cards de Modalidade, Anfitriao da Sessao e Nivel Esportivo.',
  },
  {
    id: 'map',
    label: 'Mapa',
    icon: 'map',
    eyebrow: 'Proximidade',
    title: 'Sessoes proximas',
    status: 'Espaco reservado para Mapa e Lista equivalentes de Sessoes Esportivas.',
  },
  {
    id: 'matches',
    label: 'Partidas',
    icon: 'calendarCheck',
    eyebrow: 'Participacao',
    title: 'Estados de participacao',
    status: 'Aqui entram Confirmado, Aguardando aprovacao e Recusado com icone, texto e cor.',
  },
  {
    id: 'profile',
    label: 'Perfil',
    icon: 'user',
    eyebrow: 'Perfil Esportivo',
    title: 'Seu Perfil Esportivo',
    status: 'Modalidades, Nivel Esportivo, Objetivos Esportivos e Disponibilidade alimentam a Descoberta.',
  },
]
