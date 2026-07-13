import assert from 'node:assert/strict'
import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createPinia } from 'pinia'
import { createServer } from 'vite'

globalThis.localStorage = { getItem: () => null, setItem: () => {}, removeItem: () => {} }

const server = await createServer({ server: { middlewareMode: true, hmr: false }, appType: 'custom', logLevel: 'error' })
try {
  const { default: Register } = await server.ssrLoadModule('/src/components/Register.vue')
  const app = createSSRApp({ render: () => h(Register) })
  app.use(createPinia())
  const html = await renderToString(app)

  assert.match(html, /Etapa 1 de 2/)
  assert.match(html, /Você quer marcar um esporte ou participar\?/)
  assert.match(html, /Quero marcar um esporte/)
  assert.match(html, /Quero participar/)
  assert.doesNotMatch(html, /register-email/)
} finally {
  await server.close()
}
