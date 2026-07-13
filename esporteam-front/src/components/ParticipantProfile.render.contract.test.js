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
  assert.match(html, /Estas preferencias orientam a Descoberta/)
  assert.match(html, /nao ao User de autenticacao/)
  assert.match(html, /Participante agora · Anfitriao em breve/)
  assert.doesNotMatch(html, /Salvar Perfil Esportivo/)
  assert.doesNotMatch(html, /Salvar alteracoes/)
  assert.doesNotMatch(html, /Bio Assistida/)
  assert.doesNotMatch(html, /<textarea/)
  assert.doesNotMatch(html, /class="profile-weekday-trigger"/)
  assert.doesNotMatch(html, /<select/)

  const blankProfileApp = createSSRApp({
    render: () => h(ParticipantShell, {
      sportProfileDraft: {
        profile: { display_name: '', bio: '', city: '', region: '' },
        sports: [],
        availability: [],
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
} finally {
  await server.close()
}
