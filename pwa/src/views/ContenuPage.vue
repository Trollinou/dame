<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button :default-href="coursParentInfo ? `/cours/${coursParentInfo.cours.id}` : '/tabs/apprentissage'"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ contenuActuel?.titre || 'Contenu' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">{{ contenuActuel?.titre || 'Contenu' }}</ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="isLoading" class="ion-text-center ion-padding spinner-container">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement du contenu...</p>
        </div>

        <div v-else-if="contenuActuel" class="exercice-container">
          <!-- Rendu d'une leçon -->
          <div v-if="contenuActuel.post_type === 'roi_lecon'" class="lecon-wrapper">
            <LeconReader :contenuHtml="contenuActuel.contenu_html || ''" class="lecon-content ion-padding" />
            <ion-button v-if="!estReussi" expand="block" class="ion-margin-top" @click="validerLecon">
              J'ai compris, terminer la leçon
            </ion-button>
          </div>

          <!-- Rendu d'un exercice -->
          <div v-else-if="contenuActuel.post_type === 'roi_exercice'">
            <component 
              v-if="contenuActuel.type !== undefined && getComposantExercice(contenuActuel.type)"
              :is="getComposantExercice(contenuActuel.type)" 
              :config="({ ...contenuActuel.config, id: contenuActuel.id } as any)"
              :id="contenuActuel.id"
              :key="contenuActuel.id"
              @success="onSuccess"
            />
            <div v-else class="ion-text-center ion-padding error-container">
              <p>Type d'exercice non supporté (Type {{ contenuActuel.type }}).</p>
            </div>
          </div>

          <!-- Success Card -->
          <transition name="fade">
            <ion-card v-if="estReussi" class="success-card ion-margin-top">
              <ion-card-header>
                <ion-card-title class="success-title">
                  {{ contenuActuel.post_type === 'roi_lecon' ? '🎉 Leçon terminée !' : '🎉 Exercice réussi !' }}
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <p class="success-subtitle">
                  {{ contenuActuel.post_type === 'roi_lecon' ? 'Vous avez validé cette leçon avec succès.' : 'Félicitations, vous avez trouvé la bonne séquence de coups.' }}
                </p>
                <div class="action-buttons ion-margin-top">
                  <ion-button 
                    v-if="prochainElement" 
                    expand="block" 
                    color="success" 
                    class="next-btn"
                    @click="allerAuSuivant"
                  >
                    {{ prochainElement.type === 'roi_lecon' ? 'Leçon suivante' : 'Exercice suivant' }}
                  </ion-button>
                  <ion-button 
                    v-else
                    expand="block" 
                    color="success" 
                    router-link="/tabs/apprentissage"
                  >
                    Terminer le cours
                  </ion-button>
                  <ion-button 
                    v-if="coursParentInfo"
                    expand="block" 
                    fill="outline" 
                    color="medium" 
                    :router-link="`/cours/${coursParentInfo.cours.id}`"
                  >
                    Retour au cours
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>
          </transition>
        </div>

        <div v-else class="ion-text-center ion-padding error-container">
          <p>Impossible de charger ce contenu.</p>
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
import TypePartieHeros from './types/TypePartieHeros.vue';
import TypePosiPlan from './types/TypePosiPlan.vue';
import TypeAssociPlan from './types/TypeAssociPlan.vue';
import LeconReader from '@/components/apprentissage/LeconReader.vue';

const route = useRoute();
const router = useRouter();
const apprentissageStore = useApprentissageStore();
const isLoading = ref(true);
const estReussi = ref(false);

const contenuActuel = computed(() => apprentissageStore.contenuActuel);

const coursParentInfo = computed(() => {
  if (!contenuActuel.value || apprentissageStore.parcours.length === 0) {
    return null;
  }
  for (const cours of apprentissageStore.parcours) {
    const idx = cours.playlist.findIndex(item => item.id === contenuActuel.value?.id);
    if (idx !== -1) {
      return { cours, idx };
    }
  }
  return null;
});

const prochainElement = computed(() => {
  const info = coursParentInfo.value;
  if (!info) return null;
  const { cours, idx } = info;
  if (idx < cours.playlist.length - 1) {
    return cours.playlist[idx + 1];
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
  if (type === 4) {
    return TypePartieHeros;
  }
  if (type === 5) {
    return TypePosiPlan;
  }
  if (type === 6) {
    return TypeAssociPlan;
  }
  return null;
};

const onSuccess = async () => {
  if (contenuActuel.value) {
    await apprentissageStore.validerElement(contenuActuel.value.id);
  }
  estReussi.value = true;
};

const validerLecon = async () => {
  if (contenuActuel.value) {
    await apprentissageStore.validerElement(contenuActuel.value.id);
  }
  estReussi.value = true;
};

const allerAuSuivant = () => {
  if (prochainElement.value) {
    router.push(`/contenu/${prochainElement.value.id}`);
  }
};

const loadContenu = async (idVal: any) => {
  isLoading.value = true;
  estReussi.value = false;
  const id = parseInt(Array.isArray(idVal) ? idVal[0] : idVal, 10);
  if (!isNaN(id)) {
    await apprentissageStore.fetchContenu(id);
    if (apprentissageStore.parcours.length === 0) {
      await apprentissageStore.fetchParcours();
    }
    if (apprentissageStore.elementsValides.length === 0) {
      await apprentissageStore.fetchProgression();
    }
    if (apprentissageStore.elementsValides.includes(id)) {
      estReussi.value = true;
    }
  }
  isLoading.value = false;
};

watch(
  () => route.params.id,
  async (newId) => {
    if (newId) {
      await loadContenu(newId);
    }
  },
  { immediate: true }
);

watch(
  () => contenuActuel.value,
  (newContenu) => {
    if (newContenu?.titre) {
      document.title = newContenu.titre;
    } else {
      document.title = 'Contenu';
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

.lecon-content {
  background: var(--ion-card-background, var(--ion-item-background, #fff));
  border-radius: 8px;
  line-height: 1.6;
  font-size: 1.1rem;
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
