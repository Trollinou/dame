<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/agenda"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="benevolat" v-safe-html="benevolat.title.rendered"></ion-title>
        <ion-title v-else>Participation</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="benevolat" class="safe-area-wrapper">
        <!-- Titre et Description -->
        <div class="header-section">
          <h1 v-safe-html="benevolat.title.rendered"></h1>
          <div v-if="benevolat.content?.rendered" class="description-content" v-safe-html="benevolat.content.rendered"></div>
        </div>

        <ion-list-header class="ion-no-padding">
          <ion-label>Choisissez vos créneaux</ion-label>
        </ion-list-header>

        <!-- Options de participation -->
        <ion-list lines="full" class="options-list">
          <div v-for="(day, dIndex) in benevolat.dame_benevolat_data" :key="dIndex">
            <ion-item-divider color="light">
              <ion-label>{{ formatDate(day.date) }}</ion-label>
            </ion-item-divider>
            
            <ion-item v-for="(slot, tIndex) in day.time_slots" :key="tIndex" :disabled="isDayPast(day.date)">
              <ion-checkbox 
                :checked="selectedChoices.includes(`${dIndex}_${tIndex}`)"
                @ion-change="toggleChoiceKey(`${dIndex}_${tIndex}`)"
                :disabled="isDayPast(day.date)"
                label-placement="start"
                justify="space-between"
              >
                <div style="display: flex; flex-direction: column;">
                  <span>{{ slot.start }} - {{ slot.end }}</span>
                  <span style="font-size: 0.8em; opacity: 0.7;">
                    {{ getSlotParticipantCount(dIndex, tIndex) }} inscrit(s)
                  </span>
                </div>
              </ion-checkbox>
            </ion-item>
          </div>
        </ion-list>

        <!-- Bouton de validation ou Message de fin -->
        <div class="ion-padding-vertical">
          <ion-button 
            v-if="!isPollFullyPast"
            expand="block" 
            :disabled="selectedChoices.length === 0 || isSubmitting"
            @click="submitVote"
          >
            <ion-spinner v-if="isSubmitting" name="crescent"></ion-spinner>
            <span v-else>{{ hasInitialVote ? 'Mettre à jour ma participation' : 'Confirmer ma participation' }}</span>
          </ion-button>
          <div v-else class="poll-ended-container">
            <p class="ion-text-center poll-ended-msg">
              Cet appel est terminé. Vous pouvez consulter vos participations ci-dessus.
            </p>
          </div>
        </div>
      </div>

      <!-- Chargement ou Introuvable -->
      <div v-else class="ion-text-center ion-padding mt-large">
        <div v-if="benevolatStore.isLoading">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement...</p>
        </div>
        <div v-else>
          <h2>Appel introuvable</h2>
          <ion-button expand="block" fill="outline" router-link="/tabs/benevolat" class="ion-margin-top">
            Retour à la liste
          </ion-button>
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
  IonButtons,
  IonBackButton,
  IonList,
  IonListHeader,
  IonItem,
  IonItemDivider,
  IonLabel,
  IonCheckbox,
  IonButton,
  IonSpinner,
  IonIcon,
  toastController,
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useBenevolatStore } from '@/stores/benevolat';
import { useAuthStore } from '@/stores/auth';
import { checkmarkCircle, alertCircle } from 'ionicons/icons';

const route = useRoute();
const benevolatStore = useBenevolatStore();
const authStore = useAuthStore();
const benevolatId = parseInt(route.params.id as string);

const benevolat = computed(() => benevolatStore.benevolats.find(b => b.id === benevolatId));

/**
 * Récupère le nombre d'inscrits pour un créneau
 */
const getSlotParticipantCount = (dayIndex: number | string, timeIndex: number | string) => {
  const choiceKey = `${dayIndex}_${timeIndex}`;
  return benevolatStore.reponses.filter(r => 
    r.benevolat_id === benevolatId && 
    Array.isArray(r.choices) && 
    r.choices.includes(choiceKey)
  ).length;
};

/**
 * Calcul de la date locale au format YYYY-MM-DD
 */
const getTodayStr = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const todayStr = getTodayStr();

/**
 * Vérifie si une journée est passée
 */
const isDayPast = (date: string) => {
  if (!date) return false;
  return date < todayStr;
};

/**
 * Vérifie si l'appel est entièrement terminé
 */
const isPollFullyPast = computed(() => {
  if (!benevolat.value || !Array.isArray(benevolat.value.dame_benevolat_data)) return false;
  return benevolat.value.dame_benevolat_data.every(day => isDayPast(day.date));
});

// État de la participation
const selectedChoices = ref<string[]>([]);
const isSubmitting = ref(false);
const hasInitialVote = ref(false);

/**
 * Bascule manuelle des choix
 */
const toggleChoiceKey = (key: string) => {
  const index = selectedChoices.value.indexOf(key);
  if (index > -1) {
    selectedChoices.value.splice(index, 1);
  } else {
    selectedChoices.value.push(key);
  }
};

/**
 * Récupère le vote existant de l'utilisateur (Aligné sur API v4.5.0)
 */
const fetchMyVote = async () => {
  if (!authStore.token) return;
  
  try {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/benevolats/${benevolatId}/my-vote`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    });

    if (response.ok) {
      const data = await response.json();
      selectedChoices.value = Array.isArray(data.choices) ? data.choices : [];
      if (selectedChoices.value.length > 0) {
        hasInitialVote.value = true;
        benevolatStore.markAsVoted(benevolatId);
      }
    }
  } catch (error) {
    console.error("Erreur fetchMyVote:", error);
  }
};

/**
 * Formate la date en français
 */
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('fr-FR', { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long'
  }).format(date);
};

/**
 * Soumission du vote vers l'API sécurisée (Aligné sur API v4.5.0)
 */
const submitVote = async () => {
  if (selectedChoices.value.length === 0 || !authStore.token) return;
  
  isSubmitting.value = true;
  
  try {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/benevolats/${benevolatId}/vote`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authStore.token}`
      },
      body: JSON.stringify({ 
        choices: selectedChoices.value,
        member_id: authStore.selectedIdentity?.member_id
      })
    });

    const data = await response.json();

    if (response.ok) {
      const toast = await toastController.create({
        message: hasInitialVote.value ? 'Participation mise à jour avec succès !' : 'Participation enregistrée avec succès !',
        duration: 2500,
        color: 'success',
        position: 'bottom'
      });
      await toast.present();
      hasInitialVote.value = true;
      benevolatStore.markAsVoted(benevolatId);
      
      await benevolatStore.fetchBenevolatsData(true);
    } 
    else if (response.status === 403 && data.code === 'already_voted') {
      const toast = await toastController.create({
        message: 'Vous avez déjà proposé votre aide pour cet appel.',
        duration: 3000,
        color: 'warning',
        icon: alertCircle
      });
      await toast.present();
    }
    else {
      throw new Error(data.message || 'Erreur lors de l\'envoi.');
    }
    
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.message || 'Une erreur est survenue lors de l\'envoi.',
      duration: 4000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    isSubmitting.value = false;
  }
};

// Chargement des données au montage/entrée
onIonViewWillEnter(async () => {
  if (benevolatStore.benevolats.length === 0) {
    await benevolatStore.fetchBenevolatsData();
  }
  await fetchMyVote();
});
</script>

<script lang="ts">
export default {
  name: 'BenevolatVotePage'
};
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.header-section {
  margin-bottom: 24px;
}

h1 {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 8px;
}

.description-content {
  color: var(--ion-color-medium);
  line-height: 1.4;
  font-size: 0.95rem;
}

.poll-ended-container {
  margin-top: 10px;
}

.poll-ended-msg {
  color: var(--ion-color-medium);
  font-style: italic;
  background: var(--ion-color-light);
  padding: 16px;
  border-radius: 8px;
  margin: 0;
}

.options-list {
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 20px;
}

.mt-large {
  margin-top: 10%;
}

:deep(.description-content p) {
  margin-bottom: 8px;
}
</style>
