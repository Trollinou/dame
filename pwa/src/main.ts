import { createApp } from 'vue'
import App from './App.vue'
import router from './router';

import { IonicVue } from '@ionic/vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import { vSafeHtml } from './directives/safeHtml';
import { VueQueryPlugin, QueryClient, focusManager } from '@tanstack/vue-query';
import { App as CapacitorApp } from '@capacitor/app';

/* Core CSS required for Ionic components to work properly */
import '@ionic/vue/css/core.css';

/* Basic CSS for apps built with Ionic */
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';

/* Optional CSS utils that can be commented out */
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/float-elements.css';
import '@ionic/vue/css/text-alignment.css';
import '@ionic/vue/css/text-transformation.css';
import '@ionic/vue/css/flex-utils.css';
import '@ionic/vue/css/display.css';

/**
 * Ionic Dark Mode
 * -----------------------------------------------------
 * For more info, please see:
 * https://ionicframework.com/docs/theming/dark-mode
 */

/* @import '@ionic/vue/css/palettes/dark.always.css'; */
/* @import '@ionic/vue/css/palettes/dark.class.css'; */
import '@ionic/vue/css/palettes/dark.system.css';

/* Theme variables */
import './theme/variables.css';
import { registerSW } from 'virtual:pwa-register';

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: true,
      staleTime: 1000 * 60 * 5, // 5 minutes
    },
  },
});

// Lier le focusManager de TanStack Query au cycle de vie natif de Capacitor
try {
  CapacitorApp.addListener('appStateChange', ({ isActive }) => {
    focusManager.setFocused(isActive);
  });
} catch (e) {
  console.warn("Capacitor App listener non disponible :", e);
}

const app = createApp(App)
  .use(IonicVue)
  .use(pinia)
  .use(router)
  .use(VueQueryPlugin, { queryClient });

app.directive('safe-html', vSafeHtml);

router.isReady().then(() => {
  app.mount('#app');
  // Enregistrement du Service Worker pour le support hors-ligne
  registerSW({ immediate: true });
});
