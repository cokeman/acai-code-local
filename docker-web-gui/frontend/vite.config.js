import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: '../dist',
    emptyOutDir: true,
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:9090',
        changeOrigin: true,
      },
      '/local': {
        target: 'http://localhost:9090',
        changeOrigin: true,
      },
    },
  },
})
