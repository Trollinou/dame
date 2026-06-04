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
import { useBenevolatStore } from './benevolat';
import { useTournamentStore } from './tournament';
import { useNewsStore } from './news';

export interface Identity {
  id: string;
  name: string;
  type: 'member' | 'representative';
  member_id: number;
  firstname?: string;
  elo_standard?: number | string;
  elo_rapide?: number | string;
  elo_blitz?: number | string;
}

export const useAuthStore = defineStore('auth', () => {
  const isLoading = ref(false);
  
  const getStoredToken = () => {
    const t = localStorage.getItem('dame_jwt_token');
    return (t === 'null' || t === 'undefined' || !t) ? '' : t;
  };

  const getStoredUser = () => {
    const u = localStorage.getItem('dame_user');
    try {
      return (u === 'null' || u === 'undefined' || !u) ? null : JSON.parse(u);
    } catch { return null; }
  };

  const getStoredIdentity = () => {
    const i = localStorage.getItem('dame_selected_identity');
    try {
      return (i === 'null' || i === 'undefined' || !i) ? null : JSON.parse(i);
    } catch { return null; }
  };

  const token = ref(getStoredToken());
  const user = ref<any>(getStoredUser());
  const selectedIdentity = ref<Identity | null>(getStoredIdentity());
  const adminMode = ref(false);
  
  const isAuthenticated = computed(() => !!token.value && token.value.length > 10);
  
  const userRoles = computed(() => {
    const roles = user.value?.roles;
    if (Array.isArray(roles)) return roles;
    if (typeof roles === 'object' && roles !== null) return Object.values(roles);
    return [];
  });
  
  const isAdmin = computed(() => {
    if (!isAuthenticated.value) return false;
    const roles = userRoles.value;
    const privilegedRoles = ['administrator', 'editor', 'staff', 'entraineur'];
    
    // Détection ultra-souple
    return roles.some(role => {
      if (typeof role !== 'string') return false;
      return privilegedRoles.includes(role.toLowerCase());
    });
  });

  const selectIdentity = (identity: Identity) => {
    selectedIdentity.value = identity;
    localStorage.setItem('dame_selected_identity', JSON.stringify(identity));
  };

  const login = async (username: string, password: string) => {
    if (!username || !password) return;
    isLoading.value = true;

    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/simple-jwt-login/v1/auth`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });

      const data = await response.json();
      const jwtToken = data.jwt || (data.data && data.data.jwt);

      if (response.ok && jwtToken) {
        token.value = jwtToken;
        localStorage.setItem('dame_jwt_token', token.value);

        // Récupérer le profil complet via l'API WordPress standard
        let roles: string[] = [];
        let displayName = username;
        let email = '';

        try {
          const profileRes = await fetch(`${import.meta.env.VITE_API_BASE_URL}/wp/v2/users/me?context=edit`, {
            headers: { 'Authorization': `Bearer ${token.value}` }
          });

          if (profileRes.ok) {
            const profile = await profileRes.json();
            if (profile.roles) roles = profile.roles;
            if (profile.name) displayName = profile.name;
            if (profile.email) email = profile.email;
          }
        } catch (e) {
          console.warn("Profil complet non accessible, utilisation des données d'identifiants.");
        }

        user.value = {
          name: displayName,
          email: email,
          roles: roles
        };

        localStorage.setItem('dame_user', JSON.stringify(user.value));

        // 2. Vérification des identités (familles)
        await checkIdentities(token.value);

      } else {
        throw new Error(data.message || (data.data && data.data.message) || "Erreur d'identifiants");
      }
    } catch (error: any) {
      const alert = await alertController.create({
        header: 'Échec de connexion',
        message: error.message || "Erreur serveur.",
        buttons: ['OK']
      });
      await alert.present();
    } finally {
      isLoading.value = false;
    }
  };

  const checkIdentities = async (jwtToken: string) => {
    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/my-identities`, {
        headers: { 'Authorization': `Bearer ${jwtToken}` }
      });

      if (!response.ok) throw new Error();

      const identities: Identity[] = await response.json();

      if (identities.length === 1) {
        selectIdentity(identities[0]);
        router.push('/tabs/home');
      } else if (identities.length > 1) {
        router.push('/tabs/select-person');
      } else {
        const virtualIdentity: Identity = {
          id: 'wp_virtual',
          name: user.value?.name || 'Gestionnaire',
          type: 'member',
          member_id: 0
        };
        selectIdentity(virtualIdentity);
        router.push('/tabs/home');
      }
    } catch (error) {
      router.push('/tabs/home');
    }
  };

  const logout = () => {
    token.value = '';
    user.value = null;
    selectedIdentity.value = null;
    adminMode.value = false;
    localStorage.removeItem('dame_jwt_token');
    localStorage.removeItem('dame_user');
    localStorage.removeItem('dame_selected_identity');
    useAgendaStore().clearData();
    useContactStore().clearData();
    useDashboardStore().clearData();
    useMemberStore().clearData();
    useMessageStore().clearData();
    useBenevolatStore().clearData();
    useTournamentStore().clearData();
    useNewsStore().clearData();
    router.push('/tabs/home');
  };

  const isRoiActive = ref(localStorage.getItem('dame_roi_active') !== 'false');
  const stockfishUrl = ref(localStorage.getItem('dame_stockfish_url') || '');

  const fetchPwaConfig = async () => {
    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/pwa-config`);
      if (response.ok) {
        const data = await response.json();
        isRoiActive.value = !!data.roi_active;
        stockfishUrl.value = data.stockfish_url || '';
        localStorage.setItem('dame_roi_active', String(isRoiActive.value));
        localStorage.setItem('dame_stockfish_url', stockfishUrl.value);
      }
    } catch (error) {
      console.warn("Erreur chargement pwa-config, utilisation du cache :", error);
    }
  };

  return {
    token, user, selectedIdentity, adminMode,
    isAuthenticated, isAdmin, isLoading,
    login, logout, selectIdentity, checkIdentities,
    isRoiActive, stockfishUrl, fetchPwaConfig
  };
}, {
  persist: true
});
