<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/apprentissage"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ exerciceActuel?.titre || 'Exercice' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">{{ exerciceActuel?.titre || 'Exercice' }}</ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="isLoading" class="ion-text-center ion-padding spinner-container">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement de l'exercice...</p>
        </div>

        <div v-else-if="exerciceActuel" class="exercice-container">
          <component 
            v-if="getComposantExercice(exerciceActuel.type)"
            :is="getComposantExercice(exerciceActuel.type)" 
            :config="{ ...exerciceActuel.config, id: exerciceActuel.id }"
            :key="exerciceActuel.id"
            @success="onSuccess"
          />
          <div v-else class="ion-text-center ion-padding error-container">
            <p>Type d'exercice non supporté (Type {{ exerciceActuel.type }}).</p>
          </div>

          <!-- Success Card -->
          <transition name="fade">
            <ion-card v-if="estReussi" class="success-card ion-margin-top">
              <ion-card-header>
                <ion-card-title class="success-title">🎉 Exercice réussi !</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <p class="success-subtitle">Félicitations, vous avez trouvé la bonne séquence de coups.</p>
                <div class="action-buttons ion-margin-top">
                  <ion-button 
                    v-if="prochainExercice" 
                    expand="block" 
                    color="success" 
                    class="next-btn"
                    @click="allerAuSuivant"
                  >
                    Exercice suivant : {{ prochainExercice.titre }}
                  </ion-button>
                  <ion-button 
                    expand="block" 
                    fill="outline" 
                    color="medium" 
                    router-link="/tabs/apprentissage"
                  >
                    Retour aux exercices
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>
          </transition>
        </div>

        <div v-else class="ion-text-center ion-padding error-container">
          <p>Impossible de charger cet exercice.</p>
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
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButton
} from '@ionic/vue';
import { ref, computed, watch, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useApprentissageStore } from '@/stores/apprentissage';
import TypeABCDaire from './types/TypeABCDaire.vue';
import Type100Commandements from './types/Type100Commandements.vue';
import TypePopEchecs from './types/TypePopEchecs.vue';

const route = useRoute();
const router = useRouter();
const apprentissageStore = useApprentissageStore();
const isLoading = ref(true);
const estReussi = ref(false);

const exerciceActuel = computed(() => apprentissageStore.exerciceActuel);

const prochainExercice = computed(() => {
  if (!exerciceActuel.value || apprentissageStore.listeExercices.length === 0) {
    return null;
  }
  const currentIndex = apprentissageStore.listeExercices.findIndex(ex => ex.id === exerciceActuel.value?.id);
  if (currentIndex !== -1 && currentIndex < apprentissageStore.listeExercices.length - 1) {
    return apprentissageStore.listeExercices[currentIndex + 1];
  }
  return null;
});

const getComposantExercice = (type: number) => {
  if (type === 1) {
    return Type100Commandements;
  }
  if (type === 2) {
    return TypePopEchecs;
  }
  if (type === 3) {
    return TypeABCDaire;
  }
  return null;
};

const onSuccess = () => {
  estReussi.value = true;
};

const allerAuSuivant = () => {
  if (prochainExercice.value) {
    router.push(`/exercice/${prochainExercice.value.id}`);
  }
};

const loadExercice = async (idVal: any) => {
  isLoading.value = true;
  estReussi.value = false;
  const id = parseInt(Array.isArray(idVal) ? idVal[0] : idVal, 10);
  if (!isNaN(id)) {
    await apprentissageStore.fetchExercice(id);
    if (apprentissageStore.listeExercices.length === 0) {
      await apprentissageStore.fetchListeExercices();
    }
  }
  isLoading.value = false;
};

watch(
  () => route.params.id,
  async (newId) => {
    if (newId) {
      await loadExercice(newId);
    }
  },
  { immediate: true }
);

watch(
  () => exerciceActuel.value,
  (newEx) => {
    if (newEx?.titre) {
      document.title = newEx.titre;
    } else {
      document.title = 'Exercice';
    }
  },
  { immediate: true }
);

onUnmounted(() => {
  document.title = 'Echiquier Lédonien';
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.spinner-container, .error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.exercice-container {
  max-width: 600px;
  margin: 0 auto;
}

.success-card {
  border-left: 5px solid var(--ion-color-success);
  margin-top: 16px;
  background: var(--ion-card-background, var(--ion-item-background, #fff));
  border-radius: 8px;
}

.success-title {
  color: var(--ion-color-success);
  font-weight: bold;
  font-size: 1.25rem;
}

.success-subtitle {
  font-size: 1rem;
  color: var(--ion-color-step-600, #666);
  margin: 0;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.next-btn {
  font-weight: 600;
}

/* Transistions */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
