import { createMemoryHistory, createRouter, createWebHistory } from 'vue-router'
import { participantNavigationRedirect } from './features/participant/navigation.js'

const DEFAULT_ROUTE_COMPONENTS = {
  login: () => import('./components/Login.vue'),
  register: () => import('./components/Register.vue'),
  participant: () => import('./views/ParticipantModeView.vue'),
}

export function createAppRouter({ history = createWebHistory(), components = DEFAULT_ROUTE_COMPONENTS } = {}) {
  return createRouter({
    history,
    routes: [
      { path: '/', redirect: '/descobrir' },
      { path: '/entrar', name: 'login', component: components.login, meta: { guestOnly: true } },
      { path: '/cadastro', name: 'register', component: components.register, meta: { guestOnly: true } },
      { path: '/descobrir', name: 'discover', component: components.participant, meta: { requiresAuth: true, participantTab: 'discover' } },
      { path: '/mapa', name: 'map', component: components.participant, meta: { requiresAuth: true, participantTab: 'map' } },
      { path: '/eventos', name: 'events', component: components.participant, meta: { requiresAuth: true, participantTab: 'matches' } },
      { path: '/perfil', name: 'profile', component: components.participant, meta: { requiresAuth: true, participantTab: 'profile' } },
      { path: '/sessao/:id', name: 'session', component: components.participant, meta: { requiresAuth: true } },
      { path: '/:pathMatch(.*)*', redirect: '/descobrir' },
    ],
    scrollBehavior: () => ({ top: 0 }),
  })
}

export function installAuthGuards(router, store) {
  router.beforeEach((to) => {
    return participantNavigationRedirect(to, store.auth)
  })
}

export const router = createAppRouter({
  history: typeof window === 'undefined' ? createMemoryHistory() : createWebHistory(),
})
