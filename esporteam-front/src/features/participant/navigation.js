export function participantNavigationRedirect(to, authenticated) {
  if (to.meta?.requiresAuth && !authenticated) {
    return { name: 'login', query: { retorno: to.fullPath } }
  }
  if (to.meta?.guestOnly && authenticated) return { name: 'discover' }
  return true
}
