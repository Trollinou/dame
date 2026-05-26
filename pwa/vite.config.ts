import vue from '@vitejs/plugin-vue'
import path from 'path'
import { defineConfig } from 'vite'

// https://vitejs.dev/config/
export default defineConfig({
  base: './', // Chemins relatifs pour les assets (indispensable pour WordPress)
  plugins: [
    vue()
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
// --- NOUVELLE SECTION D'OPTIMISATION ---
  build: {
    target: 'es2022', // Indispensable pour supporter les BigInt (0xffn) de chess.js
    chunkSizeWarningLimit: 800, // On augmente légèrement la tolérance pour Ionic
    modulePreload: false, // Désactive le préchargement automatique (résout les warnings "unused preload" dans WordPress)
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
