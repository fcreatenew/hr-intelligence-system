import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      // HR Intelligence API (prediksi model ML).
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
      },
      // Payroll Intelligence API (analitik payroll).
      // Prefix sengaja `/payroll-api`, bukan `/payroll`, agar tidak bentrok
      // dengan route SPA `/payroll`.
      '/payroll-api': {
        target: 'http://127.0.0.1:8300',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/payroll-api/, ''),
      },
    },
  },
})
