import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import './styles/cola-ai-theme.css'
import App from './App.vue'
import { installAuthGuards, router } from './router'
import { useAppStore } from './stores/app'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

const store = useAppStore(pinia)
await store.hydrateFromToken()
installAuthGuards(router, store)

app.use(router).mount('#app')
