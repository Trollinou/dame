/// <reference types="vitest" />

import legacy from '@vitejs/plugin-legacy'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { defineConfig } from 'vite'

// https://vitejs.dev/config/
export default defineConfig({
  base: './',
  plugins: [
    vue(),
    legacy()
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
// --- NOUVELLE SECTION D'OPTIMISATION ---
  build: {
    chunkSizeWarningLimit: 800, // On augmente légèrement la tolérance pour Ionic
    rollupOptions: {
      output: {
        manualChunks: {
          // On sépare le moteur Vue.js
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          // On sépare le moteur Ionic (très lourd)
          'ionic-vendor': ['@ionic/vue', '@ionic/vue-router', 'ionicons']
        }
      }
    }
  },
  // ---------------------------------------  
  test: {
    globals: true,
    environment: 'jsdom'
  }
})
