import { defineStore } from 'pinia'
import {
  SEED_FEEDBACKS,
  SEED_IDEAS,
  SEED_COMPETITORS,
  SEED_COMPETITOR_FEATURES,
} from '../mock/data'
import { STR } from '../mock/i18n'
import {
  loadToken,
  saveToken,
  login as apiLogin,
  register as apiRegister,
  listWorkspaces,
  createWorkspace as apiCreateWorkspace,
  selectWorkspace,
  fetchMe,
  logoutOnAuth,
  listIdeas as apiListIdeas,
  createIdea as apiCreateIdea,
} from '../services/api'

// RICE: score = (reach × impact × confidence) / effort × 3 + sqrt(votes) × 12
export function recomputeScore(idea) {
  const { reach, impact, confidence, effort } = idea.breakdown
  const base = (reach * impact * confidence) / Math.max(effort, 0.5)
  const boost = Math.sqrt(idea.votes || 0) * 12
  return Math.round((base * 3 + boost) * 10) / 10
}

function seedIdeas() {
  return SEED_IDEAS.map(i => {
    const idea = { ...i, breakdown: { ...i.breakdown } }
    idea.score = recomputeScore(idea)
    return idea
  })
}

let toastTimer = null

export const useAppStore = defineStore('app', {
  state: () => ({
    auth: false,
    authView: 'login',              // login | register
    token: loadToken(),
    currentUser: null,
    currentWorkspace: null,
    workspaceSetupRequired: false,
    workspaceOptions: [],
    workspaceSetupError: null,
    workspaceSetupLoading: false,
    loginError: null,
    loginLoading: false,
    registerError: null,
    registerErrors: null,
    registerLoading: false,
    page: 'inbox',                  // inbox | ideas | competitors | roadmap
    publicMode: false,
    lang: 'pt',
    theme: 'light',
    clusteringState: 'idle',        // idle | running | done
    feedbacks: SEED_FEEDBACKS.map(f => ({ ...f })),
    ideas: seedIdeas(),
    inboxIdeas: [],                 // Ideas reais do backend (Idea = entrada bruta no PRD)
    inboxLoading: false,
    inboxError: null,
    inboxFilters: { source: 'all', clustered: 'all' },
    competitors: SEED_COMPETITORS.map(c => ({ ...c })),
    competitorFeatures: SEED_COMPETITOR_FEATURES.map(f => ({ ...f })),
    votes: [],                      // { ideaId, email, at }
    selectedIdeaId: null,
    selectedCompetitorId: 'comp-1',
    pasteAnalyzing: false,
    pendingComanderoFeatures: null,
    revealedPending: 0,
    toast: null,
    loopState: 'idle',              // idle | step1 | step2 | step3 | step4 | done
  }),
  getters: {
    isAuthenticated: (s) => s.auth,
  },
  actions: {
    setAuth(v)     { this.auth = v },
    setAuthView(v) { this.authView = v; this.loginError = null; this.registerError = null; this.registerErrors = null },

    async login(email, password) {
      this.loginError = null
      this.loginLoading = true
      try {
        const { token } = await apiLogin(email, password)
        if (!token) throw new Error('login_no_token')
        saveToken(token)
        this.token = token

        const workspaces = await listWorkspaces()
        if (!workspaces.length) {
          const me = await fetchMe()
          this.currentUser = me?.user ?? null
          this.currentWorkspace = null
          this.workspaceOptions = []
          this.workspaceSetupRequired = true
          this.auth = true
          return
        }

        await this.selectAndLoadWorkspace(workspaces[0])
      } catch (err) {
        this.loginError = err?.response?.data?.message || err?.message || 'login_failed'
        saveToken(null)
        this.token = null
        this.auth = false
      } finally {
        this.loginLoading = false
      }
    },

    async register({ name, email, password, passwordConfirmation, workspaceName }) {
      this.registerError = null
      this.registerErrors = null
      this.registerLoading = true
      try {
        const { token } = await apiRegister({ name, email, password, passwordConfirmation })
        if (!token) throw new Error('register_no_token')
        saveToken(token)
        this.token = token

        const workspace = await apiCreateWorkspace({ name: workspaceName })
        if (!workspace?.id) throw new Error('workspace_create_failed')

        const { token: scopedToken } = await selectWorkspace(workspace.id)
        saveToken(scopedToken)
        this.token = scopedToken

        const me = await fetchMe()
        this.currentUser = me?.user ?? null
        this.currentWorkspace = me?.workspace ?? workspace
        this.workspaceSetupRequired = false
        this.workspaceOptions = []
        this.auth = true
        this.authView = 'login'
        this.loadInboxIdeas()
      } catch (err) {
        const apiErrors = err?.response?.data?.errors
        this.registerErrors = apiErrors || null
        this.registerError = err?.response?.data?.message || err?.message || 'register_failed'
        saveToken(null)
        this.token = null
        this.auth = false
      } finally {
        this.registerLoading = false
      }
    },

    async hydrateFromToken() {
      if (!this.token) return
      try {
        const me = await fetchMe()
        this.currentUser = me?.user ?? null
        this.currentWorkspace = me?.workspace ?? null
        this.auth = true
        if (this.currentWorkspace) {
          this.workspaceSetupRequired = false
          this.loadInboxIdeas()
          return
        }

        const workspaces = await listWorkspaces()
        if (workspaces.length) {
          await this.selectAndLoadWorkspace(workspaces[0])
          return
        }

        this.workspaceOptions = []
        this.workspaceSetupRequired = true
      } catch {
        saveToken(null)
        this.token = null
        this.auth = false
        this.workspaceSetupRequired = false
      }
    },

    async selectAndLoadWorkspace(workspace) {
      const workspaceId = workspace?.id ?? workspace
      const { token: scopedToken } = await selectWorkspace(workspaceId)
      if (!scopedToken) throw new Error('workspace_select_failed')
      saveToken(scopedToken)
      this.token = scopedToken

      const me = await fetchMe()
      this.currentUser = me?.user ?? this.currentUser
      this.currentWorkspace = me?.workspace ?? workspace
      this.workspaceSetupRequired = false
      this.workspaceOptions = []
      this.auth = true
      this.loadInboxIdeas()
    },

    async refreshWorkspaceOptions() {
      this.workspaceSetupError = null
      this.workspaceSetupLoading = true
      try {
        this.workspaceOptions = await listWorkspaces()
      } catch (err) {
        this.workspaceSetupError = err?.response?.data?.message || err?.message || 'workspace_list_failed'
      } finally {
        this.workspaceSetupLoading = false
      }
    },

    async createAndSelectWorkspace(name) {
      this.workspaceSetupError = null
      this.workspaceSetupLoading = true
      try {
        const workspace = await apiCreateWorkspace({ name })
        if (!workspace?.id) throw new Error('workspace_create_failed')
        await this.selectAndLoadWorkspace(workspace)
      } catch (err) {
        this.workspaceSetupError = err?.response?.data?.message || err?.message || 'workspace_create_failed'
      } finally {
        this.workspaceSetupLoading = false
      }
    },

    async chooseWorkspace(workspace) {
      this.workspaceSetupError = null
      this.workspaceSetupLoading = true
      try {
        await this.selectAndLoadWorkspace(workspace)
      } catch (err) {
        this.workspaceSetupError = err?.response?.data?.message || err?.message || 'workspace_select_failed'
      } finally {
        this.workspaceSetupLoading = false
      }
    },

    async logout() {
      await logoutOnAuth()
      saveToken(null)
      this.token = null
      this.currentUser = null
      this.currentWorkspace = null
      this.workspaceSetupRequired = false
      this.workspaceOptions = []
      this.inboxIdeas = []
      this.auth = false
    },

    async loadInboxIdeas() {
      this.inboxLoading = true
      this.inboxError = null
      try {
        const { source, clustered } = this.inboxFilters
        const params = { perPage: 200 }
        if (source && source !== 'all') params.source = source
        if (clustered === 'unclustered') params.unclustered = true
        const { items } = await apiListIdeas(params)
        if (clustered === 'clustered') {
          this.inboxIdeas = items.filter(i => i.clustered)
        } else {
          this.inboxIdeas = items
        }
      } catch (err) {
        this.inboxError = err?.response?.data?.message || err?.message || 'load_failed'
        this.inboxIdeas = []
      } finally {
        this.inboxLoading = false
      }
    },

    setInboxFilter(key, value) {
      this.inboxFilters[key] = value
      this.loadInboxIdeas()
    },

    async createInboxIdea(payload) {
      const idea = await apiCreateIdea(payload)
      if (idea?.id) this.inboxIdeas = [idea, ...this.inboxIdeas]
      return idea
    },

    setPage(p)     { this.page = p; this.publicMode = (p === 'public') },
    setLang(l)     { this.lang = l },
    setTheme(t)    { this.theme = t },
    selectIdea(id) { this.selectedIdeaId = id },
    selectCompetitor(id) { this.selectedCompetitorId = id },
    setClustering(v) { this.clusteringState = v },
    setPasteAnalyzing(v) { this.pasteAnalyzing = v },
    setPendingFeatures(v) { this.pendingComanderoFeatures = v; this.revealedPending = 0 },
    bumpRevealed(idx) { this.revealedPending = Math.max(this.revealedPending, idx + 1) },
    setLoop(v) { this.loopState = v },
    setToast(payload) {
      this.toast = payload
      if (toastTimer) clearTimeout(toastTimer)
      if (payload) {
        toastTimer = setTimeout(() => { this.toast = null; toastTimer = null }, 2400)
      }
    },
    clearToast() { this.toast = null; if (toastTimer) { clearTimeout(toastTimer); toastTimer = null } },

    updateIdea(id, patch) {
      const i = this.ideas.find(x => x.id === id)
      if (!i) return
      Object.assign(i, patch)
      if (patch.breakdown) i.breakdown = { ...i.breakdown, ...patch.breakdown }
      i.score = recomputeScore(i)
    },
    setStatus(id, status) {
      const i = this.ideas.find(x => x.id === id)
      if (i) i.status = status
    },
    vote(ideaId, email) {
      const key = ideaId + '::' + email.toLowerCase()
      const dup = this.votes.some(v => v.ideaId + '::' + v.email.toLowerCase() === key)
      if (dup) {
        this.setToast(STR.roadmap_vote_dup)
        return
      }
      const i = this.ideas.find(x => x.id === ideaId)
      if (!i) return
      i.votes = (i.votes || 0) + 1
      i.score = recomputeScore(i)
      this.votes.push({ ideaId, email, at: Date.now() })
      this.setToast(STR.roadmap_vote_thanks)
    },
    promoteFeature(featureId) {
      const cf = this.competitorFeatures.find(c => c.id === featureId)
      if (!cf) return
      const newIdeaId = `idea-cf-${cf.id}`
      if (this.ideas.find(i => i.id === newIdeaId)) return
      const newIdea = {
        id: newIdeaId,
        title: cf.name,
        description: { pt: 'Originada de gap identificado em concorrente.', en: 'Originated from a competitor gap.' },
        status: 'analysis', votes: 0, origin: 'competitor_gap',
        breakdown: { reach: 6, impact: 6, confidence: 0.7, effort: 3 },
        rationale: { pt: 'Criada automaticamente da análise de concorrente.', en: 'Auto-created from competitor analysis.' },
        tags: ['gap'],
      }
      newIdea.score = recomputeScore(newIdea)
      this.ideas.push(newIdea)
      cf.linkedIdea = newIdeaId
      cf.match = 'partial'
      this.setToast({ pt: 'Item criado e adicionado ao roadmap.', en: 'Item created and added to roadmap.' })
    },
    promotePendingFeature(feature, idx) {
      if (feature.match !== 'gap') return
      const newIdeaId = `idea-comand-${idx}`
      const newCfId = `cf-pending-${idx}`
      const newIdea = {
        id: newIdeaId, title: feature.name,
        description: { pt: 'Originada de gap identificado em concorrente.', en: 'Originated from a competitor gap.' },
        status: 'analysis', votes: 0, origin: 'competitor_gap',
        breakdown: { reach: 6, impact: 6, confidence: 0.7, effort: 3 },
        rationale: { pt: 'Criada automaticamente da análise do Comandero.', en: 'Auto-created from Comandero analysis.' },
        tags: ['gap'],
      }
      newIdea.score = recomputeScore(newIdea)
      this.ideas.push(newIdea)
      this.competitorFeatures.push({ ...feature, id: newCfId, competitorId: 'comp-3', linkedIdea: newIdeaId, match: 'partial' })
      if (this.pendingComanderoFeatures && this.pendingComanderoFeatures[idx]) {
        const p = this.pendingComanderoFeatures[idx]
        p.linkedIdea = newIdeaId
        p.match = 'partial'
        p.promoted = true
      }
      this.setToast({ pt: 'Item criado e adicionado ao roadmap.', en: 'Item created and added to roadmap.' })
    },
  },
})

// Selectors
export const feedbacksForIdea  = (s, ideaId) => s.feedbacks.filter(f => f.idea === ideaId)
export const feedbackCount      = (s, ideaId) => feedbacksForIdea(s, ideaId).length
export const votesForIdea       = (s, ideaId) => s.votes.filter(v => v.ideaId === ideaId)
