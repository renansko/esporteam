import assert from 'node:assert/strict'
import { createMemoryHistory } from 'vue-router'
import { createAppRouter, installAuthGuards } from './router.js'
import { PARTICIPANT_ROUTE_BY_TAB, resolveSessionBackAction, safeParticipantReturnPath } from './features/participant/shell.js'

const Stub = { render: () => null }
const router = createAppRouter({
  history: createMemoryHistory(),
  components: { login: Stub, register: Stub, participant: Stub },
})
const paths = router.getRoutes().map(route => route.path)
for (const path of ['/entrar', '/cadastro', '/descobrir', '/mapa', '/eventos', '/perfil', '/sessao/:id']) {
  assert(paths.includes(path), `missing public route contract: ${path}`)
}

const store = { auth: false }
installAuthGuards(router, store)
await router.push('/sessao/session-42')
assert.equal(router.currentRoute.value.name, 'login')
assert.equal(router.currentRoute.value.query.retorno, '/sessao/session-42')

store.auth = true
await router.push('/entrar')
assert.equal(router.currentRoute.value.name, 'discover')
await router.push('/eventos')
assert.equal(router.currentRoute.value.meta.participantTab, 'matches')
await router.push('/sessao/session-42')
assert.equal(router.currentRoute.value.params.id, 'session-42')

assert.equal(PARTICIPANT_ROUTE_BY_TAB.profile, 'profile')
assert.equal(safeParticipantReturnPath('/mapa'), '/mapa')
assert.equal(safeParticipantReturnPath('https://example.com'), '/descobrir')
assert.equal(safeParticipantReturnPath('//example.com'), '/descobrir')
assert.deepEqual(resolveSessionBackAction({ returnTo: '/mapa' }), { type: 'back' })
assert.deepEqual(resolveSessionBackAction({}), { type: 'replace', to: '/descobrir' })

console.log('router contracts: ok')
