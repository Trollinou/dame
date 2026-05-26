<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/benevolat"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="benevolat" v-safe-html="benevolat.title.rendered"></ion-title>
        <ion-title v-else>Détails Bénévolat</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">
            <div class="multiline-large-title" v-if="benevolat" v-safe-html="benevolat.title.rendered"></div>
            <div class="multiline-large-title" v-else>Détails Bénévolat</div>
          </ion-title>
        </ion-toolbar>
      </ion-header>

      <div v-if="benevolat" class="safe-area-wrapper">
        <!-- Carte Description -->
        <ion-card v-if="benevolat.content?.rendered" class="ion-no-margin ion-margin-bottom">
          <ion-card-header>
            <ion-card-title>Description</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="description-content" v-safe-html="benevolat.content.rendered"></div>
          </ion-card-content>
        </ion-card>

        <!-- Accordéons des dates -->
        <ion-accordion-group>
          <ion-accordion v-for="(day, dIndex) in benevolat.dame_benevolat_data" :key="dIndex" :value="'day-' + dIndex">
            <ion-item slot="header" color="light">
              <ion-label>{{ formatDate(day.date) }}</ion-label>
            </ion-item>
            <div slot="content" class="ion-padding">
              <ion-card v-for="(slot, tIndex) in day.time_slots" :key="tIndex" class="slot-card">
                <ion-card-header>
                  <ion-card-subtitle>{{ slot.start }} - {{ slot.end }}</ion-card-subtitle>
                </ion-card-header>
                <ion-card-content>
                  <div v-if="getParticipants(dIndex, tIndex).length > 0">
                    <ion-chip 
                      color="primary" 
                      v-for="participant in getParticipants(dIndex, tIndex)" 
                      :key="participant.id"
                      v-safe-html="participant.title.rendered"
                    >
                    </ion-chip>
                  </div>
                  <p v-else class="no-participants">Aucune participation</p>
                </ion-card-content>
              </ion-card>
            </div>
          </ion-accordion>
        </ion-accordion-group>
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
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonAccordion,
  IonAccordionGroup,
  IonItem,
  IonLabel,
  IonChip,
  IonSpinner,
  IonButton
} from '@ionic/vue';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useBenevolatStore } from '@/stores/benevolat';

const route = useRoute();
const benevolatStore = useBenevolatStore();
const benevolatId = parseInt(route.params.id as string);

const benevolat = computed(() => benevolatStore.benevolats.find(b => b.id === benevolatId));

/**
 * Formate la date en français
 */
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('fr-FR', { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  }).format(date);
};

/**
 * Récupère les participants pour un créneau spécifique
 */
const getParticipants = (dayIndex: number, timeIndex: number) => {
  const choiceKey = `${dayIndex}_${timeIndex}`;
  return benevolatStore.reponses.filter(r => 
    r.benevolat_id === benevolatId && 
    Array.isArray(r.choices) && 
    r.choices.includes(choiceKey)
  );
};
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.mt-large {
  margin-top: 5%;
}

.multiline-large-title {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.2;
  display: block;
  width: 100%;
  padding-bottom: 8px;
}

.description-content {
  color: var(--ion-color-dark);
  line-height: 1.5;
}

.slot-card {
  margin: 0 0 16px 0;
  box-shadow: none;
  border: 1px solid var(--ion-color-light-shade);
}

ion-chip {
  margin: 4px;
}

.no-participants {
  color: var(--ion-color-medium);
  font-style: italic;
  font-size: 0.9em;
}

:deep(.description-content p) {
  margin-top: 0;
  margin-bottom: 12px;
}
</style>
