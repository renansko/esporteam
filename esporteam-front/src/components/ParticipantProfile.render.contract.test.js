import assert from 'node:assert/strict'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createPinia } from 'pinia'
import { createServer } from 'vite'

globalThis.localStorage = { getItem: () => null, setItem: () => {}, removeItem: () => {} }

const server = await createServer({ server: { middlewareMode: true, hmr: false }, appType: 'custom', logLevel: 'error' })
try {
  const { default: ParticipantShell } = await server.ssrLoadModule('/src/components/ParticipantShell.vue')
  const { useAppStore } = await server.ssrLoadModule('/src/stores/app.js')
  const app = createSSRApp({
    setup() {
      const store = useAppStore()
      store.participantTab = 'profile'
      return () => h(ParticipantShell, {
        sportProfileDraft: {
          profile: { display_name: 'Ana Perfil', bio: 'Corredora', city: 'Florianopolis', region: 'SC' },
          sports: [{ sport_id: 1, name: 'Corrida', level: 'Iniciante', goals: ['treino'], preferred_positions: '', is_primary: true }],
          availability: [{ weekday: 6, starts_at: '08:00', ends_at: '10:00' }],
        },
        participantMatchFilters: [],
      })
    },
  })
  const pinia = createPinia()
  app.use(pinia)
  const html = await renderToString(app)
  assert.match(html, /Perfil Esportivo ativo/)
  assert.match(html, /Dados pessoais/)
  assert.match(html, /Ana Perfil/)
  assert.match(html, /Corredora/)
  assert.match(html, /Florianopolis - SC/)
  assert.match(html, /class="profile-edit-button"/)
  assert.match(html, /Adicionar modalidade|class="profile-practice-summary"/)
  assert.match(html, /Disponibilidade/)
  assert.match(html, /Estas prefer.ncias orientam a Descoberta/)
  assert.match(html, /não à conta de acesso/)
  assert.match(html, /Participante agora · Anfitriao em breve/)
  assert.doesNotMatch(html, /Salvar Perfil Esportivo/)
  assert.doesNotMatch(html, /Salvar alteracoes/)
  assert.doesNotMatch(html, /Bio Assistida/)
  assert.doesNotMatch(html, /<textarea/)
  assert.doesNotMatch(html, /class="profile-weekday-trigger"/)
  assert.doesNotMatch(html, /<select/)

  const teacherProfileApp = createSSRApp({
    render: () => h(ParticipantShell, {
      sportProfileDraft: {
        profile: { display_name: 'Marina Perfil', bio: '', city: 'Florianopolis', region: 'SC' },
        sports: [],
        availability: [],
      },
      teacherProfileDraft: {
        headline: 'Treinadora de corrida para iniciantes',
        credentials: 'CREF ativo',
        hourly_price_cents: 12000,
        service_radius_km: 15,
      },
      participantMatchFilters: [],
    }),
  })
  const teacherProfilePinia = createPinia()
  teacherProfileApp.use(teacherProfilePinia)
  useAppStore(teacherProfilePinia).participantTab = 'profile'
  const teacherProfileHtml = await renderToString(teacherProfileApp)
  assert.match(teacherProfileHtml, /Sair da conta/)
  assert.match(teacherProfileHtml, /Configurações de Professor/)
  assert.match(teacherProfileHtml, /Treinadora de corrida para iniciantes/)
  assert.match(teacherProfileHtml, /CREF ativo/)
  assert.match(teacherProfileHtml, /R\$\u00a0120,00/)
  assert.match(teacherProfileHtml, /15 km de atendimento/)
  assert.match(teacherProfileHtml, /Professor ativo/)

  const blankProfileApp = createSSRApp({
    render: () => h(ParticipantShell, {
      sportProfileDraft: {
        profile: { display_name: '', bio: '', city: '', region: '' },
        sports: [],
        availability: [],
      },
      teacherProfileDraft: {
        headline: '',
        credentials: '',
        hourly_price_cents: null,
        service_radius_km: null,
      },
      participantMatchFilters: [],
    }),
  })
  const blankProfilePinia = createPinia()
  blankProfileApp.use(blankProfilePinia)
  useAppStore(blankProfilePinia).participantTab = 'profile'
  const blankProfileHtml = await renderToString(blankProfileApp)
  assert.match(blankProfileHtml, /Nome do Perfil Esportivo/)
  assert.match(blankProfileHtml, /Definir local/)
  assert.match(blankProfileHtml, /Salvar alteracoes/)
  assert.match(blankProfileHtml, /Especialidade/)
  assert.match(blankProfileHtml, /Valor por hora/)
} finally {
  await server.close()
}
