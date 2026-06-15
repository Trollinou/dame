import vue from '@vitejs/plugin-vue'
import path from 'path'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vitejs.dev/config/
export default defineConfig({
  base: './', // Chemins relatifs pour les assets (indispensable pour WordPress)
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'assets/icon/*.png'],
      manifest: {
        name: 'Echiquier Lédonien',
        short_name: 'Echiquier Lédonien',
        description: 'Echiquier Lédonien PWA',
        theme_color: '#ffffff',
        icons: [
          {
            src: 'assets/icon/icon-192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any maskable'
          },
          {
            src: 'assets/icon/icon-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        // On s'assure que tous les assets nécessaires sont mis en cache
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        // On augmente la limite de taille pour le fichier WASM de Stockfish (environ 7Mo)
        maximumFileSizeToCacheInBytes: 10 * 1024 * 1024,
        runtimeCaching: [
          {
            urlPattern: /.*\/roi\/includes\/chess\/dist\/stockfish\.(js|wasm)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'roi-stockfish-cache',
              expiration: {
                maxEntries: 2,
                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 jours
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
        ]
      }
    })
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
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('pinia')) {
              return 'vue-vendor';
            }
            if (id.includes('@ionic') || id.includes('ionicons')) {
              return 'ionic-vendor';
            }
          }
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
