import fs from 'node:fs'
import { fileURLToPath, URL } from 'node:url'

import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  plugins: [tailwindcss(), vue(), vueDevTools()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: 'app.tm-bot.test',
    port: 5173,
    https: {
      key: fs.readFileSync('.cert/app.tm-bot.test-key.pem'),
      cert: fs.readFileSync('.cert/app.tm-bot.test.pem'),
    },
  },
})
