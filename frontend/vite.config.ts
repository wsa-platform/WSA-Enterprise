import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': 'http://127.0.0.1:8079',
      // Official Sanctum CSRF cookie lives at /sanctum, not /api.
      '/sanctum': 'http://127.0.0.1:8079',
    },
  },
  preview: {
    proxy: {
      '/api': 'http://127.0.0.1:8079',
      '/sanctum': 'http://127.0.0.1:8079',
    },
  },
})
