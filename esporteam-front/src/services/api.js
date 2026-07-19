import axios from 'axios'

const TOKEN_KEY = 'esporteam.token'

export function loadToken() {
  return localStorage.getItem(TOKEN_KEY) || null
}

export function saveToken(token) {
  if (token) localStorage.setItem(TOKEN_KEY, token)
  else localStorage.removeItem(TOKEN_KEY)
}

function makeClient(baseURL) {
  const client = axios.create({ baseURL, timeout: 10000 })
  client.interceptors.request.use((config) => {
    const token = loadToken()
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
  })
  return client
}

export const authApi = makeClient('/auth')
export const wsApi = makeClient('/ws')
export const esporteamApi = makeClient('/api')

export async function openEventConversation(sessionId, { cursor = null } = {}) {
  const { data } = await esporteamApi.get(`/sessions/${sessionId}/conversation`, { params: cursor ? { cursor } : undefined })
  return data?.data ?? data
}

export async function postEventConversationMessage(sessionId, { body, clientMessageId }) {
  const { data } = await esporteamApi.post(`/sessions/${sessionId}/conversation/messages`, {
    body,
    client_message_id: clientMessageId,
  })
  return data?.data ?? data
}

export async function login(email, password) {
  const { data } = await authApi.post('/login', { email, password })
  return data?.data ?? data
}

export async function register({ name, email, password, passwordConfirmation, registrationIntent = 'participant', birthDate, adultAttestation }) {
  const payload = {
    name,
    email,
    password,
    password_confirmation: passwordConfirmation,
  }
  if (registrationIntent === 'teacher') payload.registration_intent = registrationIntent
  if (birthDate) {
    payload.birth_date = birthDate
    payload.adult_attestation = adultAttestation
  }

  const { data } = await authApi.post('/register', payload)
  return data?.data ?? data
}

export async function completeAdultEligibility({ birthDate, adultAttestation }) {
  const { data } = await authApi.post('/adult-eligibility', {
    birth_date: birthDate,
    adult_attestation: adultAttestation,
  })
  return data?.data ?? data
}

export async function listWorkspaces() {
  const { data } = await wsApi.get('/workspaces')
  return data?.data ?? []
}

export async function createWorkspace({ name }) {
  const { data } = await wsApi.post('/workspaces', { name })
  return data?.data ?? data
}

export async function selectWorkspace(workspaceId) {
  const { data } = await authApi.post('/workspace/select', { workspace_id: workspaceId })
  return data?.data ?? data
}

export async function fetchMe() {
  const { data } = await esporteamApi.get('/me')
  return data?.data ?? data
}

export async function fetchSportProfile() {
  const { data } = await esporteamApi.get('/profile')
  return data?.data ?? null
}

export async function saveSportProfile({ displayName, city, region } = {}) {
  const payload = {
    display_name: displayName,
    visibility: 'public',
  }
  if (city) payload.city = city
  if (region) payload.region = region

  const { data } = await esporteamApi.put('/profile', payload)
  return data?.data ?? data
}

export async function updateSportProfile(payload = {}) {
  const { data } = await esporteamApi.put('/profile', payload)
  return data?.data ?? data
}

export async function updateSportProfileSports(sports = []) {
  const { data } = await esporteamApi.put('/profile/sports', { sports })
  return data?.data ?? data
}

export async function updateSportProfileAvailability(windows = []) {
  const { data } = await esporteamApi.put('/profile/availability', { windows })
  return data?.data ?? data
}

export async function fetchTeacherProfile() {
  const { data } = await esporteamApi.get('/teacher-profile')
  return data?.data ?? null
}

export async function updateTeacherProfile(payload = {}) {
  const { data } = await esporteamApi.put('/teacher-profile', payload)
  return data?.data ?? data
}

export async function listBioSuggestions({ page = 1 } = {}) {
  const { data } = await esporteamApi.get('/profile/bio-suggestions', { params: { page } })
  return {
    items: data?.data ?? [],
    meta: data?.meta ?? null,
  }
}

export async function createBioSuggestion({ instruction, idempotencyKey } = {}) {
  const payload = {}
  if (instruction?.trim()) payload.instruction = instruction.trim()

  const headers = idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined
  const { data } = await esporteamApi.post('/profile/bio-suggestions', payload, { timeout: 45000, headers })
  return data?.data ?? data
}

export async function acceptBioSuggestion(suggestionId) {
  const { data } = await esporteamApi.post(`/profile/bio-suggestions/${suggestionId}/accept`)
  return data?.data ?? data
}

export async function logoutOnAuth() {
  try {
    await authApi.post('/logout')
  } catch {
    // Best-effort: se o servidor falhar, ainda limpamos o token local.
  }
}

export async function listIdeas({ source, unclustered, perPage } = {}) {
  const params = {}
  if (source) params.source = source
  if (unclustered) params.unclustered = 1
  if (perPage) params.per_page = perPage
  const { data } = await esporteamApi.get('/ideas', { params })
  return {
    items: data?.data ?? [],
    meta: data?.meta ?? null,
  }
}

export async function createIdea({ description, title, authorEmail } = {}) {
  const payload = { description }
  if (title) payload.title = title
  if (authorEmail) payload.author_email = authorEmail
  const { data } = await esporteamApi.post('/ideas', payload)
  return data?.data ?? data
}
