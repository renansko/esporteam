import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
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
  const { useAppStore } = await server.ssrLoadModule('/src/stores/app.js')

  async function renderShell(props, { participantTab } = {}) {
    const app = createSSRApp({
      render: () => h(ParticipantShell, props),
    })

    const pinia = createPinia()
    app.use(pinia)
    const store = useAppStore(pinia)
    if (participantTab) store.setParticipantTab(participantTab)
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
    }, { participantTab: 'discover' })

  assert.match(html, /class="discovery-deck"/)
  assert.match(html, /class="session-card discovery-action-card"/)
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
  assert.match(html, /Ver detalhes/)
  assert.match(html, /Voltar/)
  assert.match(html, /Pular/)
  assert.match(html, /Pedir para participar/)
  assert.doesNotMatch(html, /capacity|capacidade|vaga|slot|remaining/i)

  const detailHtml = await renderShell({
    discoveryCards: [],
    sportSessionDetailOpen: true,
    sportSessionDetailView: {
      title: 'Corrida aberta no parque',
      description: 'Ritmo leve para Entusiastas que querem voltar a correr com constancia.',
      hostLabel: 'Marina Costa',
      hostRoleLabel: 'Organizador',
      dateTimeLabel: '12/07, 08:00',
      levelLabel: 'Iniciante',
      meetingPoint: 'Entrada principal do Parque de Coqueiros',
      participantCountLabel: '8 participantes',
      participants: [],
      rules: ['Chegar 10 minutos antes'],
      equipment: ['Tenis de corrida', 'Garrafa de agua'],
      entryBadge: {
        icon: 'check',
        label: 'Aberta',
        toneClass: 'session-entry-badge-open',
      },
      confirmed: false,
      canSubmitParticipation: true,
      primaryActionLabel: 'Vou participar',
      primaryActionIcon: 'check',
      primaryActionToneClass: 'session-detail-primary-open',
      },
    })

  assert.match(detailHtml, /aria-label="Detalhe da Sessao Esportiva"/)
  assert.match(detailHtml, /Corrida aberta no parque/)
  assert.match(detailHtml, /Aberta/)
  assert.match(detailHtml, /Ritmo leve para Entusiastas/)
  assert.match(detailHtml, /Organizador · Marina Costa/)
  assert.match(detailHtml, /12\/07, 08:00/)
  assert.match(detailHtml, /Iniciante/)
  assert.match(detailHtml, /Entrada principal do Parque de Coqueiros/)
  assert.match(detailHtml, /Chegar 10 minutos antes/)
  assert.match(detailHtml, /Tenis de corrida/)
  assert.match(detailHtml, /Vou participar/)
  assert.doesNotMatch(detailHtml, /Ana Silva/)
  assert.doesNotMatch(detailHtml, /capacity|capacidade|vaga|slot|remaining/i)

  const curatedDetailHtml = await renderShell({
    discoveryCards: [],
    sportSessionDetailOpen: true,
    sportSessionDetailView: {
      title: 'Volei com curadoria',
      description: 'Sessao guiada pelo Professor para fundamentos.',
      hostLabel: 'Luiz Pereira',
      hostRoleLabel: 'Professor',
      dateTimeLabel: '13/07, 19:00',
      levelLabel: 'Iniciante',
      meetingPoint: 'Posto 3',
      participantCountLabel: '5 participantes',
      participants: [],
      rules: ['Chegar antes'],
      equipment: ['Agua'],
      entryBadge: {
        icon: 'lock',
        label: 'Com curadoria',
        toneClass: 'session-entry-badge-curated',
      },
      confirmed: false,
      approvalNotice: 'O Anfitriao da Sessao revisa os pedidos antes de confirmar sua participacao.',
      canSubmitParticipation: true,
      primaryActionLabel: 'Pedir para participar',
      primaryActionIcon: 'lock',
      primaryActionToneClass: 'session-detail-primary-curated',
      },
    })

  assert.match(curatedDetailHtml, /class="session-entry-badge session-entry-badge-curated"/)
  assert.match(curatedDetailHtml, /Com curadoria/)
  assert.match(curatedDetailHtml, /O Anfitriao da Sessao revisa os pedidos/)
  assert.match(curatedDetailHtml, /Pedir para participar/)
  assert.doesNotMatch(curatedDetailHtml, /capacity|capacidade|vaga|slot|remaining/i)

  const confirmedDetailHtml = await renderShell({
    discoveryCards: [],
    sportSessionDetailOpen: true,
    sportSessionParticipationConfirmed: true,
    sportSessionDetailView: {
      title: 'Corrida aberta no parque',
      description: 'Ritmo leve para Entusiastas.',
      hostLabel: 'Marina Costa',
      hostRoleLabel: 'Organizador',
      dateTimeLabel: '12/07, 08:00',
      levelLabel: 'Iniciante',
      meetingPoint: 'Entrada principal',
      rules: [],
      equipment: [],
      participants: ['Ana Silva'],
      entryBadge: {
        icon: 'check',
        label: 'Aberta',
        toneClass: 'session-entry-badge-open',
      },
      confirmed: true,
      participationFeedback: 'Confirmado',
      canSubmitParticipation: false,
      primaryActionLabel: 'Confirmado',
      primaryActionIcon: 'check',
      primaryActionToneClass: 'session-detail-primary-open',
    },
  })

  assert.match(confirmedDetailHtml, /Confirmado/)
  assert.match(confirmedDetailHtml, /Ana Silva/)
  assert.doesNotMatch(confirmedDetailHtml, /Vou participar/)

  const pendingDetailHtml = await renderShell({
    discoveryCards: [],
    sportSessionDetailOpen: true,
    sportSessionDetailView: {
      title: 'Volei com curadoria',
      description: 'Sessao guiada pelo Professor.',
      hostLabel: 'Luiz Pereira',
      hostRoleLabel: 'Professor',
      dateTimeLabel: '13/07, 19:00',
      levelLabel: 'Iniciante',
      meetingPoint: 'Posto 3',
      rules: [],
      equipment: [],
      participants: [],
      entryBadge: {
        icon: 'lock',
        label: 'Com curadoria',
        toneClass: 'session-entry-badge-curated',
      },
      confirmed: false,
      participationFeedback: 'Aguardando aprovacao',
      participationFeedbackTone: 'pending',
      canSubmitParticipation: false,
      primaryActionLabel: 'Aguardando aprovacao',
      primaryActionIcon: 'lock',
      primaryActionToneClass: 'session-detail-primary-curated',
    },
  })

  assert.match(pendingDetailHtml, /Aguardando aprovacao/)
  assert.match(pendingDetailHtml, /session-detail-feedback session-detail-feedback-pending/)

  const nearbyMapHtml = await renderShell({
    discoveryCards: [],
    nearbySessions: [
      {
        id: 'nearby-open',
        distanceKm: 2.4,
        entryMode: 'publica_direta',
        participantCount: 8,
        session: {
          id: 'nearby-session-open',
          title: 'Corrida no parque',
          modality: { id: 'mod-corrida', name: 'Corrida' },
          hostSportProfile: { id: 'host-open', displayName: 'Marina Costa', role: 'Organizador' },
          startsAt: '2026-07-12T08:00:00-03:00',
          location: { label: 'Parque de Coqueiros' },
          entryMode: 'publica_direta',
          participantCount: 8,
        },
      },
      {
        id: 'nearby-curated',
        distanceKm: 5.1,
        entryMode: 'publica_aprovacao',
        entryRule: 'approval_required',
        participantCount: 5,
        session: {
          id: 'nearby-session-curated',
          title: 'Volei tecnico',
          modality: { id: 'mod-volei', name: 'Volei de praia' },
          hostSportProfile: { id: 'host-curated', displayName: 'Luiz Pereira', role: 'Professor' },
          startsAt: '2026-07-13T19:00:00-03:00',
          location: { label: 'Beira-mar Norte' },
          entryMode: 'publica_aprovacao',
          entryRule: 'approval_required',
          requiresApproval: true,
          participantCount: 5,
        },
      },
    ],
    nearbySurfaceMode: 'map',
    nearbySelectedSessionId: 'nearby-open',
  }, { participantTab: 'map' })

  assert.match(nearbyMapHtml, /Alternar entre Mapa e Lista/)
  assert.match(nearbyMapHtml, /class="[^"]*\bnearby-map\b[^"]*"/)
  assert.doesNotMatch(nearbyMapHtml, /\+ Criar sessão/)
  assert.match(nearbyMapHtml, /Corrida/)
  assert.match(nearbyMapHtml, /08:00/)
  assert.match(nearbyMapHtml, /Resumo da Sessao Esportiva/)
  assert.match(nearbyMapHtml, /Vou participar/)
  assert.match(nearbyMapHtml, /Ver detalhes/)
  assert.doesNotMatch(nearbyMapHtml, /Modalidade a definir/)
  assert.doesNotMatch(nearbyMapHtml, /Disponibilidade a definir/)

  const publicationBeforeLocationHtml = await renderShell({
    discoveryCards: [],
    nearbySessions: [],
    nearbySurfaceMode: 'map',
    oneOffPublication: {
      open: { value: true },
      draft: { value: { latitude: '', longitude: '' } },
      sports: { value: [] },
      selectedLocation: { value: null },
      canReview: { value: false },
      loading: { value: false },
      error: { value: '' },
      close: () => {},
      selectLocation: () => {},
      publishDraft: async () => null,
    },
  }, { participantTab: 'map' })

  assert.equal((publicationBeforeLocationHtml.match(/nearby-real-map-canvas/g) || []).length, 1)
  assert.match(publicationBeforeLocationHtml, /nearby-real-map--selecting/)
  assert.doesNotMatch(publicationBeforeLocationHtml, /class="publication-panel"/)

  const nearbyMapSource = await readFile(new URL('./NearbySessionsMap.vue', import.meta.url), 'utf8')
  assert.match(nearbyMapSource, /watch\(\(\) => props\.selectable/)
  assert.match(nearbyMapSource, /emit\('create-session', location\)/)
  assert.match(nearbyMapSource, /bubblingMouseEvents: false/)

  const publicationHtml = await renderShell({
    discoveryCards: [],
    nearbySessions: [],
    nearbySurfaceMode: 'map',
    oneOffPublication: {
      open: { value: true },
      draft: {
        value: {
          sport_id: '',
          title: '',
          starts_at: '',
          ends_at: '',
          meeting_point_label: '',
          city: 'Florianópolis',
          region: 'SC',
          latitude: -27.5969,
          longitude: -48.5494,
          capacity: '',
          description: '',
          entry_mode: 'publica_direta',
        },
      },
      sports: { value: [] },
      selectedLocation: { value: { latitude: -27.5969, longitude: -48.5494 } },
      canReview: { value: false },
      loading: { value: false },
      error: { value: '' },
      close: () => {},
      selectLocation: () => {},
      publishDraft: async () => null,
    },
  }, { participantTab: 'map' })

  assert.equal((publicationHtml.match(/nearby-real-map-canvas/g) || []).length, 1)
  assert.match(publicationHtml, /Modalidade/)
  assert.match(publicationHtml, /Cadastre uma Modalidade no seu Perfil Esportivo/)
  assert.match(publicationHtml, /class="publication-submit"/)
  assert.doesNotMatch(publicationHtml, /ID da modalidade|latitude|longitude|-27\.59690|-48\.54940|adult_eligibility/i)

  const nearbyListHtml = await renderShell({
    discoveryCards: [],
    nearbySessions: [
      {
        id: 'nearby-open',
        distanceKm: 2.4,
        entryMode: 'publica_direta',
        participantCount: 8,
        session: {
          id: 'nearby-session-open',
          title: 'Corrida no parque',
          modality: { id: 'mod-corrida', name: 'Corrida' },
          hostSportProfile: { id: 'host-open', displayName: 'Marina Costa', role: 'Organizador' },
          startsAt: '2026-07-12T08:00:00-03:00',
          location: { label: 'Parque de Coqueiros' },
          entryMode: 'publica_direta',
          participantCount: 8,
        },
      },
      {
        id: 'nearby-curated',
        distanceKm: 5.1,
        entryMode: 'publica_aprovacao',
        entryRule: 'approval_required',
        participantCount: 5,
        session: {
          id: 'nearby-session-curated',
          title: 'Volei tecnico',
          modality: { id: 'mod-volei', name: 'Volei de praia' },
          hostSportProfile: { id: 'host-curated', displayName: 'Luiz Pereira', role: 'Professor' },
          startsAt: '2026-07-13T19:00:00-03:00',
          location: { label: 'Beira-mar Norte' },
          entryMode: 'publica_aprovacao',
          entryRule: 'approval_required',
          requiresApproval: true,
          participantCount: 5,
        },
      },
    ],
    nearbySurfaceMode: 'list',
    nearbySelectedSessionId: 'nearby-curated',
    nearbySessionParticipationFeedback: 'Aguardando aprovacao',
    nearbySessionParticipationFeedbackTone: 'pending',
  }, { participantTab: 'map' })

  assert.match(nearbyListHtml, /class="nearby-list"/)
  assert.match(nearbyListHtml, /Corrida no parque/)
  assert.match(nearbyListHtml, /Volei tecnico/)
  assert.match(nearbyListHtml, /Professor · Luiz Pereira/)
  assert.match(nearbyListHtml, /Aguardando aprovacao/)
  assert.match(nearbyListHtml, /session-detail-feedback session-detail-feedback-pending/)

  const loadingHtml = await renderShell({
    nearbySessions: [],
    nearbySessionsLoading: true,
  })

  assert.match(loadingHtml, /aria-busy="true"/)
  assert.match(loadingHtml, /nearby-stage-skeleton|loading-grace/)

  const defaultEmptyHtml = await renderShell({
    nearbySessions: [],
  })

  assert.match(defaultEmptyHtml, /Mapa e Lista preparados/)
  assert.doesNotMatch(defaultEmptyHtml, /Abra o mapa/)
  assert.doesNotMatch(defaultEmptyHtml, /segunda chance/i)

  const emptyNearbyMapHtml = await renderShell({
    discoveryCards: [],
    nearbySessions: [],
  }, { participantTab: 'map' })

  assert.match(emptyNearbyMapHtml, /Mapa real de Sessões Esportivas próximas/)
  assert.match(emptyNearbyMapHtml, /Nenhuma Sessão Esportiva próxima/)
  assert.match(emptyNearbyMapHtml, /Nenhuma Sessão Esportiva por aqui ainda/)
  assert.match(emptyNearbyMapHtml, /Quando um Organizador publicar/)
  assert.doesNotMatch(emptyNearbyMapHtml, /Atualizar mapa/)
  assert.doesNotMatch(emptyNearbyMapHtml, /discovery-empty-map/)

  const errorHtml = await renderShell({
    discoveryCards: [],
    discoveryError: {
      title: 'Descoberta sem atualizacao',
      description: 'Nao foi possivel atualizar a Descoberta agora. Verifique sua conexao e tente novamente.',
      retryLabel: 'Tentar novamente',
    },
  }, { participantTab: 'discover' })

  assert.match(errorHtml, /Descoberta sem atualizacao/)
  assert.match(errorHtml, /Tentar novamente/)
} finally {
  await server.close()
}
