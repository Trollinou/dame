<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/survey"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="sondage">{{ sondage.title.raw }}</ion-title>
        <ion-title v-else>Détails Sondage</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">
            <div class="multiline-large-title" v-if="sondage">{{ sondage.title.raw }}</div>
            <div class="multiline-large-title" v-else>Détails Sondage</div>
          </ion-title>
        </ion-toolbar>
      </ion-header>

      <div v-if="sondage">
        <!-- Carte Description -->
        <ion-card v-if="sondage.content?.rendered">
          <ion-card-header>
            <ion-card-title>Description</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="description-content" v-html="sondage.content.rendered"></div>
          </ion-card-content>
        </ion-card>

        <!-- Accordéons des dates -->
        <ion-accordion-group>
          <ion-accordion v-for="(day, dIndex) in sondage.dame_sondage_data" :key="dIndex" :value="'day-' + dIndex">
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
                    >
                      {{ participant.title.raw }}
                    </ion-chip>
                  </div>
                  <p v-else class="no-participants">Aucun inscrit</p>
                </ion-card-content>
              </ion-card>
            </div>
          </ion-accordion>
        </ion-accordion-group>
      </div>

      <!-- Chargement ou Introuvable -->
      <div v-else class="ion-text-center ion-padding mt-large">
        <div v-if="sondageStore.isLoading">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement du sondage...</p>
        </div>
        <div v-else>
          <h2>Sondage introuvable</h2>
          <ion-button expand="block" fill="outline" router-link="/tabs/survey" class="ion-margin-top">
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
import { useSondageStore } from '@/stores/sondages';

const route = useRoute();
const sondageStore = useSondageStore();
const sondageId = parseInt(route.params.id as string);

const sondage = computed(() => sondageStore.sondages.find(s => s.id === sondageId));

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
  return sondageStore.reponses.filter(r => 
    r.sondage_id === sondageId && 
    Array.isArray(r.choices) && 
    r.choices.includes(choiceKey)
  );
};
</script>

<style scoped>
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
