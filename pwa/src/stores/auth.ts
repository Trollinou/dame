import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { toastController, alertController } from '@ionic/vue';
import router from '../router';

// Import des autres stores pour nettoyage
import { useAgendaStore } from './agenda';
import { useContactStore } from './contacts';
import { useDashboardStore } from './dashboard';
import { useMemberStore } from './members';
import { useMessageStore } from './messages';
import { useSondageStore } from './sondages';

export const useAuthStore = defineStore('auth', () => {
  const isLoading = ref(false);
  let isFetching = false;
  
  // Initialisation sécurisée (évite les chaînes "null" ou "undefined")
  const getStoredToken = () => {
    const t = localStorage.getItem('dame_jwt_token');
    return (t === 'null' || t === 'undefined' || !t) ? '' : t;
  };

  const getStoredUser = () => {
    const u = localStorage.getItem('dame_user');
    try {
      return (u === 'null' || u === 'undefined' || !u) ? null : JSON.parse(u);
    } catch {
      return null;
    }
  };

  const token = ref(getStoredToken());
  const user = ref<any>(getStoredUser());
  const adminMode = ref(false);
  
  const isAuthenticated = computed(() => !!token.value && token.value.length > 10);
  
  const userRoles = computed(() => user.value?.roles || []);
  
  const isAdmin = computed(() => {
    if (!isAuthenticated.value) return false;
    const roles = userRoles.value;
    return roles.includes('administrator') || roles.includes('editor') || roles.includes('staff');
  });

  /**
   * Connexion à l'API WordPress via JWT
   */
  const login = async (username: string, password: string) => {
    if (!username || !password) return;

    isLoading.value = true;

    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/jwt-auth/v1/token`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
      });

      const data = await response.json();

      if (response.ok && data.token) {
        token.value = data.token;
        user.value = {
          name: data.user_display_name,
          email: data.user_email,
          roles: data.user_roles || ['administrator']
        };
        
        localStorage.setItem('dame_jwt_token', token.value);
        localStorage.setItem('dame_user', JSON.stringify(user.value));

        const toast = await toastController.create({
          message: "Connexion réussie ! Bienvenue.",
          duration: 2000,
          color: 'success',
          position: 'bottom'
        });
        await toast.present();

        router.push('/tabs/home');
      } else {
        throw new Error(data.message || "Identifiants incorrects.");
      }
    } catch (error: any) {
      const alert = await alertController.create({
        header: 'Échec de connexion',
        message: error.message || "Impossible de contacter le serveur.",
        buttons: ['OK']
      });
      await alert.present();
    } finally {
      isLoading.value = false;
      isFetching = false;
    }
  };

  /**
   * Déconnexion
   */
  const logout = () => {
    console.log("Exécution de logout...");
    
    // 1. Vider l'état réactif
    token.value = '';
    user.value = null;
    
    // 2. Vider le stockage local
    localStorage.removeItem('dame_jwt_token');
    localStorage.removeItem('dame_user');

    // 3. Nettoyer tous les autres stores
    useAgendaStore().clearData();
    useContactStore().clearData();
    useDashboardStore().clearData();
    useMemberStore().clearData();
    useMessageStore().clearData();
    useSondageStore().clearData();

    // 4. Rediriger vers login
    router.push('/tabs/home');
  };

  return {
    token,
    user,
    adminMode,
    isAuthenticated,
    isAdmin,
    isLoading,
    login,
    logout
  };
});
