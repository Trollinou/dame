import { defineStore } from 'pinia';
import { ref } from 'vue';
import { toastController, alertController } from '@ionic/vue';
import router from '../router';

export const useAuthStore = defineStore('auth', () => {
  const isLoading = ref(false);
  const token = ref(localStorage.getItem('dame_jwt_token') || '');

  /**
   * Connexion à l'API WordPress via JWT
   */
  const login = async (username: string, password: string) => {
    if (!username || !password) return;

    isLoading.value = true;

    try {
      // Note: On utilise la variable d'environnement pour la racine de l'API
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
        localStorage.setItem('dame_jwt_token', data.token);

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
    }
  };

  /**
   * Déconnexion
   */
  const logout = () => {
    token.value = '';
    localStorage.removeItem('dame_jwt_token');
    router.push('/login');
  };

  return {
    token,
    isLoading,
    login,
    logout
  };
});
