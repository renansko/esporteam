import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(({ mode }) => {
  const env = { ...process.env, ...loadEnv(mode, process.cwd(), '') }
  const apiTarget = env.VITE_API_TARGET || 'http://127.0.0.1:8000'
  const authTarget = env.VITE_AUTH_TARGET || 'http://127.0.0.1:8001'
  const workspaceTarget = env.VITE_WORKSPACE_TARGET || 'http://127.0.0.1:8002'

  return {
    plugins: [vue(), tailwindcss()],
    server: {
      host: true,
      port: 5173,
      strictPort: true,
      // The frontend runs in Docker while the source is bind-mounted from WSL.
      // Native filesystem events are unreliable across that boundary.
      watch: {
        usePolling: true,
        interval: 100,
      },
      proxy: {
        '/api': { target: apiTarget, changeOrigin: true },
        '/auth': {
          target: authTarget,
          changeOrigin: true,
          rewrite: (p) => p.replace(/^\/auth/, '/api/auth'),
        },
        '/ws': {
          target: workspaceTarget,
          changeOrigin: true,
          rewrite: (p) => p.replace(/^\/ws/, '/api'),
        },
      },
    },
  }
})
