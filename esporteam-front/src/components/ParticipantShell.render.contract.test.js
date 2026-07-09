import assert from 'node:assert/strict'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createPinia } from 'pinia'
import { createServer } from 'vite'

globalThis.localStorage = {
  getItem: () => null,
  setItem: () => {},
  removeItem: () => {},
}

const server = await createServer({
  server: { middlewareMode: true, hmr: false },
  appType: 'custom',
  logLevel: 'error',
})

try {
  const { default: ParticipantShell } = await server.ssrLoadModule('/src/components/ParticipantShell.vue')

  async function renderShell(props) {
    const app = createSSRApp({
      render: () => h(ParticipantShell, props),
    })

    app.use(createPinia())
    return renderToString(app)
  }

  const html = await renderShell({
      discoveryCards: [
        {
          id: 'card-render-1',
          type: 'session',
          distanceKm: 4.8,
          recommendationReason: 'Boa compatibilidade com sua Disponibilidade',
          entryMode: 'publica_aprovacao',
          entryRule: 'approval_required',
          participantCount: 7,
          vacancyStatus: 'hidden',
          session: {
            id: 'session-render-1',
            title: 'Corrida orientada no parque',
            modality: { id: 'mod-corrida', name: 'Corrida' },
            hostSportProfile: {
              id: 'host-render-1',
              displayName: 'Marina Costa',
              role: 'Organizador',
            },
            startsAt: '2026-07-14T07:30:00-03:00',
            location: { label: 'Parque de Coqueiros' },
            level: 'Iniciante a Intermediario',
            participantCount: 7,
            requiresApproval: true,
          },
        },
      ],
    })

  assert.match(html, /class="discovery-deck"/)
  assert.match(html, /class="session-card"/)
  assert.match(html, /Filtros/)
  assert.match(html, /aria-label="Sessao Esportiva Corrida orientada no parque/)
  assert.match(html, /class="session-entry-badge session-entry-badge-curated"/)
  assert.match(html, /Com curadoria/)
  assert.match(html, /Corrida orientada no parque/)
  assert.match(html, /Corrida/)
  assert.match(html, /Organizador · Marina Costa/)
  assert.match(html, /4,8 km/)
  assert.match(html, /14\/07, 07:30/)
  assert.match(html, /Iniciante a Intermediario/)
  assert.match(html, /7 participantes/)
  assert.match(html, /Boa compatibilidade com sua Disponibilidade/)
  assert.doesNotMatch(html, /Voltar|Pular|Tenho interesse/)
  assert.doesNotMatch(html, /capacity|capacidade|vaga|slot|remaining/i)

  const loadingHtml = await renderShell({
    discoveryCards: [],
    discoveryLoading: true,
  })

  assert.match(loadingHtml, /aria-label="Descoberta carregando"/)
  assert.match(loadingHtml, /session-card-skeleton/)

  const emptyHtml = await renderShell({
    discoveryCards: [],
    hasDiscoveryFilters: true,
    discoveryFilters: {
      sportSlug: 'corrida',
      level: 'iniciante',
      goal: 'treino',
      distanceKm: 20,
      weekday: 'sabado',
      startsAt: '08:00',
      endsAt: '10:00',
      participationType: 'open',
    },
  })

  assert.match(emptyHtml, /Filtros ativos: Corrida · 20 km · Iniciante · Treino · Sabado · 08:00-10:00 · Aberta/)
  assert.match(emptyHtml, /Nenhuma Sessao Esportiva por perto/)
  assert.match(emptyHtml, /Amplie a distancia/)

  const defaultEmptyHtml = await renderShell({
    discoveryCards: [],
  })

  assert.match(defaultEmptyHtml, /Amplie a distancia/)

  const errorHtml = await renderShell({
    discoveryCards: [],
    discoveryError: {
      title: 'Descoberta sem atualizacao',
      description: 'Nao foi possivel atualizar a Descoberta agora. Verifique sua conexao e tente novamente.',
      retryLabel: 'Tentar novamente',
    },
  })

  assert.match(errorHtml, /Descoberta sem atualizacao/)
  assert.match(errorHtml, /Tentar novamente/)
} finally {
  await server.close()
}
