import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import './styles/cola-ai-theme.css'
import App from './App.vue'

createApp(App).use(createPinia()).mount('#app')
