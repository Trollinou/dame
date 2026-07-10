<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Mon Profil</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        
        <!-- ÉTAT CONNECTÉ -->
        <div v-if="authStore.isAuthenticated" class="profile-container">
          <!-- En-tête du profil -->
          <div class="profile-header">
            <div class="avatar-container">
              <ion-icon :icon="personCircleOutline" class="avatar-icon"></ion-icon>
            </div>
            <h2>{{ authStore.selectedIdentity?.name || authStore.user?.name }}</h2>
            <p class="email">{{ authStore.user?.email }}</p>
            <span class="badge" :class="identityBadgeClass">
              {{ identityTypeText }}
            </span>
          </div>

          <!-- Section administration (si admin) -->
          <div v-if="authStore.isAdmin" class="admin-access-card ion-margin-bottom">
            <div class="admin-access-content">
              <ion-icon :icon="shieldCheckmarkOutline" color="primary" class="admin-icon"></ion-icon>
              <div>
                <h3>Espace Administration</h3>
                <p>Vous disposez des accès de gestion du club.</p>
              </div>
            </div>
            <ion-button 
              expand="block" 
              color="primary" 
              fill="solid" 
              class="ion-margin-top"
              @click="goToAdmin"
            >
              <ion-icon slot="start" :icon="settingsOutline"></ion-icon>
              Accéder à l'espace Administration
            </ion-button>
          </div>

          <!-- Informations & ELO (si adhérent) -->
          <div v-if="authStore.selectedIdentity?.type === 'member'" class="info-card ion-margin-bottom">
            <h3 class="card-title">
              <ion-icon :icon="trophyOutline" color="primary"></ion-icon>
              Classements ELO
            </h3>
            <div class="elo-grid">
              <div class="elo-item">
                <span class="elo-label">Standard</span>
                <span class="elo-val">{{ authStore.selectedIdentity?.elo_standard || 'N/A' }}</span>
              </div>
              <div class="elo-item">
                <span class="elo-label">Rapide</span>
                <span class="elo-val">{{ authStore.selectedIdentity?.elo_rapide || 'N/A' }}</span>
              </div>
              <div class="elo-item">
                <span class="elo-label">Blitz</span>
                <span class="elo-val">{{ authStore.selectedIdentity?.elo_blitz || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <!-- Membres Associés / Famille -->
          <div v-if="hasAssociatedMembers" class="info-card ion-margin-bottom">
            <h3 class="card-title">
              <ion-icon :icon="peopleOutline" color="primary"></ion-icon>
              Membres associés
            </h3>
            <ion-list lines="none">
              <ion-item 
                v-for="member in authStore.selectedIdentity?.associated_members" 
                :key="member.member_id"
                class="associated-member-item"
              >
                <ion-icon slot="start" :icon="personOutline" class="member-icon"></ion-icon>
                <ion-label>
                  <h4>{{ member.firstname }} {{ member.name || '' }}</h4>
                  <p>Adhérent</p>
                </ion-label>
              </ion-item>
            </ion-list>
          </div>

          <!-- Actions de profil -->
          <div class="actions-container">
            <ion-button 
              v-if="hasMultipleIdentities" 
              expand="block" 
              fill="outline" 
              class="ion-margin-bottom"
              @click="changeIdentity"
            >
              <ion-icon slot="start" :icon="swapHorizontalOutline"></ion-icon>
              Changer de profil actif
            </ion-button>

            <!-- Déconnexion tout en bas -->
            <ion-button 
              expand="block" 
              color="danger" 
              fill="solid"
              class="logout-button"
              @click="handleLogout"
            >
              <ion-icon slot="start" :icon="logOutOutline"></ion-icon>
              Se déconnecter
            </ion-button>
          </div>
        </div>

        <!-- ÉTAT NON CONNECTÉ -->
        <div v-else class="login-prompt">
          <div class="prompt-header">
            <ion-icon :icon="lockOpenOutline" class="prompt-icon"></ion-icon>
            <h2>Bienvenue sur l'application</h2>
            <p>Connectez-vous ou créez un compte pour accéder à votre espace adhérent, vos cours d'apprentissage, et gérer vos informations.</p>
          </div>
          
          <div class="prompt-actions">
            <ion-button expand="block" color="primary" router-link="/login" class="ion-margin-bottom">
              <ion-icon slot="start" :icon="logInOutline"></ion-icon>
              Se connecter
            </ion-button>
            <ion-button expand="block" fill="outline" color="primary" router-link="/register">
              <ion-icon slot="start" :icon="personAddOutline"></ion-icon>
              Créer un compte
            </ion-button>
          </div>
        </div>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButton,
  IonIcon,
  IonItem,
  IonLabel,
  IonList
} from '@ionic/vue';
import {
  personCircleOutline,
  trophyOutline,
  peopleOutline,
  personOutline,
  logOutOutline,
  swapHorizontalOutline,
  shieldCheckmarkOutline,
  settingsOutline,
  lockOpenOutline,
  logInOutline,
  personAddOutline
} from 'ionicons/icons';
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const identityTypeText = computed(() => {
  const type = authStore.selectedIdentity?.type;
  if (type === 'admin') return 'Administrateur';
  if (type === 'representative') return 'Responsable Légal';
  return 'Adhérent';
});

const identityBadgeClass = computed(() => {
  const type = authStore.selectedIdentity?.type;
  if (type === 'admin') return 'badge-admin';
  if (type === 'representative') return 'badge-rep';
  return 'badge-member';
});

const hasAssociatedMembers = computed(() => {
  const members = authStore.selectedIdentity?.associated_members;
  return Array.isArray(members) && members.length > 0;
});

const hasMultipleIdentities = computed(() => {
  const identities = authStore.user?.associated_members || authStore.selectedIdentity?.associated_members;
  return Array.isArray(identities) && identities.length > 0;
});

const changeIdentity = () => {
  router.push('/select-person');
};

const goToAdmin = () => {
  authStore.adminMode = true;
  router.push('/admin/dashboard');
};

const handleLogout = () => {
  authStore.logout();
};
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
  max-width: 600px;
  margin: 0 auto;
}

.profile-container {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 20px 0 25px;
  text-align: center;
}

.avatar-container {
  margin-bottom: 12px;
}

.avatar-icon {
  font-size: 80px;
  color: var(--ion-color-step-350, #8a8a8f);
}

.profile-header h2 {
  font-weight: 700;
  margin: 0 0 4px 0;
  font-size: 24px;
}

.profile-header .email {
  color: var(--ion-color-step-600, #666);
  margin: 0 0 12px 0;
  font-size: 14px;
}

.badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-admin {
  background-color: var(--ion-color-danger-light, #ffdae0);
  color: var(--ion-color-danger, #eb445a);
}

.badge-rep {
  background-color: var(--ion-color-warning-light, #ffd59a);
  color: var(--ion-color-warning-shade, #b58000);
}

.badge-member {
  background-color: var(--ion-color-primary-light, #d2e3fc);
  color: var(--ion-color-primary, #3880ff);
}

.admin-access-card {
  background: var(--ion-color-primary-light, #d2e3fc);
  color: var(--ion-color-primary-shade, #1a5cff);
  border-radius: 12px;
  padding: 16px;
  border: 1px dashed var(--ion-color-primary);
}

.admin-access-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.admin-access-content h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--ion-color-primary-shade, #1a5cff);
}

.admin-access-content p {
  margin: 0;
  font-size: 13px;
  color: var(--ion-color-step-700, #444);
}

.admin-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.info-card {
  background: var(--ion-card-background, var(--ion-item-background, #fff));
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  padding: 16px;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  margin-top: 0;
  margin-bottom: 16px;
}

.card-title ion-icon {
  font-size: 20px;
}

.elo-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  text-align: center;
}

.elo-item {
  display: flex;
  flex-direction: column;
  background: var(--ion-color-light, #f4f5f8);
  padding: 10px;
  border-radius: 8px;
}

.elo-label {
  font-size: 11px;
  color: var(--ion-color-step-600, #666);
  text-transform: uppercase;
  margin-bottom: 4px;
}

.elo-val {
  font-size: 18px;
  font-weight: 700;
  color: var(--ion-color-dark, #000);
}

.associated-member-item {
  --background: var(--ion-color-light, #f4f5f8);
  --border-radius: 8px;
  margin-bottom: 8px;
}

.member-icon {
  color: var(--ion-color-medium);
}

.actions-container {
  margin-top: 20px;
}

.logout-button {
  margin-top: 16px;
}

.login-prompt {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  padding: 40px 20px;
}

.prompt-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 40px;
}

.prompt-icon {
  font-size: 80px;
  color: var(--ion-color-primary);
  margin-bottom: 24px;
}

.prompt-header h2 {
  font-weight: 700;
  margin-bottom: 12px;
  font-size: 24px;
}

.prompt-header p {
  color: var(--ion-color-step-600, #666);
  line-height: 1.5;
  max-width: 320px;
  margin: 0;
}
</style>
