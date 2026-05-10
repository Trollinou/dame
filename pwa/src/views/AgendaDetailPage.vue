<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/agenda"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="event" v-html="event.title.rendered"></ion-title>
        <ion-title v-else>Détails Événement</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">
            <div class="multiline-large-title" v-if="event" v-html="event.title.rendered"></div>
            <div class="multiline-large-title" v-else>Détails Événement</div>
          </ion-title>
        </ion-toolbar>
      </ion-header>

      <div v-if="event">
        <!-- Carte Détails -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="calendarOutline" color="primary"></ion-icon>
              Détails
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list lines="none">
              <ion-item>
                <ion-label>
                  <p>Date et Heure</p>
                  <h3>{{ formatEventDate(event) }}</h3>
                </ion-label>
              </ion-item>
              <ion-item v-if="event.meta._dame_competition_type">
                <ion-label>
                  <p>Type de compétition</p>
                  <h3>{{ event.meta._dame_competition_type }}</h3>
                </ion-label>
              </ion-item>
              <ion-item v-if="event.meta._dame_level">
                <ion-label>
                  <p>Niveau</p>
                  <h3>{{ event.meta._dame_level }}</h3>
                </ion-label>
              </ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>

        <!-- Carte Lieu -->
        <ion-card v-if="event.meta._dame_location_name || event.meta._dame_address">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="locationOutline" color="primary"></ion-icon>
              Lieu
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list lines="none">
              <ion-item>
                <ion-label class="ion-text-wrap">
                  <h3 v-if="event.meta._dame_location_name"><strong>{{ event.meta._dame_location_name }}</strong></h3>
                  <p v-if="event.meta._dame_address">{{ event.meta._dame_address }}</p>
                  <p v-if="event.meta._dame_postal_code || event.meta._dame_city">
                    {{ event.meta._dame_postal_code }} {{ event.meta._dame_city }}
                  </p>
                </ion-label>
                <ion-button 
                  slot="end" 
                  fill="clear" 
                  :href="mapUrl" 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  <ion-icon slot="icon-only" :icon="locationOutline"></ion-icon>
                </ion-button>
              </ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>

        <!-- Carte Description -->
        <ion-card v-if="processedDescription.cleanHtml">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="informationCircleOutline" color="primary"></ion-icon>
              Description
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="description-content" v-html="processedDescription.cleanHtml"></div>
            
            <!-- Bouton d'inscription HelloAsso -->
            <ion-button 
              v-if="processedDescription.registrationUrl" 
              expand="block" 
              class="ion-margin-top" 
              :href="processedDescription.registrationUrl" 
              target="_blank"
            >
              S'inscrire à l'événement
            </ion-button>
          </ion-card-content>
        </ion-card>
      </div>

      <!-- Chargement ou Introuvable -->
      <div v-else class="ion-text-center ion-padding mt-large">
        <div v-if="agendaStore.isLoading">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement de l'événement...</p>
        </div>
        <div v-else>
          <ion-icon :icon="calendarOutline" size="large" color="medium"></ion-icon>
          <h2>Événement introuvable</h2>
          <p>Cet événement n'existe pas ou la liste n'est pas encore chargée.</p>
          <ion-button expand="block" fill="outline" router-link="/tabs/agenda" class="ion-margin-top">
            Retour à l'agenda
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
  IonCardContent,
  IonList,
  IonItem,
  IonLabel,
  IonIcon,
  IonButton,
  IonSpinner,
  isPlatform
} from '@ionic/vue';
import {
  calendarOutline,
  locationOutline,
  informationCircleOutline
} from 'ionicons/icons';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '@/stores/agenda';

const route = useRoute();
const agendaStore = useAgendaStore();

const eventId = parseInt(route.params.id as string);

/**
 * Récupère l'événement correspondant dans le store
 */
const event = computed(() => {
  return agendaStore.events.find(e => e.id === eventId);
});

/**
 * Génère l'URL de la carte selon la plateforme (iOS, Android, Web)
 */
const mapUrl = computed(() => {
  if (!event.value) return '#';
  
  const meta = event.value.meta;
  const fullAddress = `${meta._dame_address || ''} ${meta._dame_postal_code || ''} ${meta._dame_city || ''}`.trim();
  
  if (!fullAddress) return '#';

  const encodedAddress = encodeURIComponent(fullAddress);

  if (isPlatform('ios')) {
    return `http://maps.apple.com/?q=${encodedAddress}`;
  } else if (isPlatform('android')) {
    return `geo:0,0?q=${encodedAddress}`;
  } else {
    // Fallback Web / Desktop
    return `https://www.google.com/maps/search/?api=1&query=${encodedAddress}`;
  }
});

/**
 * Analyse la description pour extraire le shortcode HelloAsso
 */
const processedDescription = computed(() => {
  const rawHtml = event.value?._dame_agenda_description_html || event.value?.meta._dame_agenda_description || '';
  let cleanHtml = rawHtml;
  let registrationUrl = null;

  // Regex pour détecter [helloasso campaign="URL"]
  const regex = /\[helloasso\s+campaign="([^"]+)"[^\]]*\]/i;
  const match = rawHtml.match(regex);

  if (match && match[1]) {
    registrationUrl = match[1];
    // Supprimer le shortcode du HTML
    cleanHtml = rawHtml.replace(match[0], '');
  }

  return { cleanHtml, registrationUrl };
});

/**
 * Convertit une date YYYY-MM-DD en DD/MM/YYYY
 */
const formatPart = (dateString: string): string => {
  if (!dateString) return '';
  return dateString.split('-').reverse().join('/');
};

/**
 * Formate la date de l'événement de manière intelligente
 */
const formatEventDate = (event: AgendaEvent): string => {
  const meta = event.meta;
  const startDate = meta?._dame_start_date;
  const endDate = meta?._dame_end_date;

  if (!startDate) return 'Date non définie';

  if (endDate && startDate !== endDate) {
    return `Du ${formatPart(startDate)} au ${formatPart(endDate)}`;
  }

  const startTime = meta?._dame_start_time;
  const endTime = meta?._dame_end_time;
  const isAllDay = meta?._dame_all_day === 1;

  if (startTime && endTime && !isAllDay) {
    return `Le ${formatPart(startDate)} de ${startTime} à ${endTime}`;
  }

  return `Le ${formatPart(startDate)} (Toute la journée)`;
};
</script>

<style scoped>
.mt-large {
  margin-top: 5%;
}

ion-card {
  margin-bottom: 20px;
}

ion-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1.1em;
  font-weight: bold;
}

p {
  color: var(--ion-color-medium);
  font-size: 0.85em;
  margin-bottom: 2px;
}

h3 {
  font-weight: 500;
  margin-top: 0;
  margin-bottom: 2px;
}

.description-content {
  color: var(--ion-color-dark);
  line-height: 1.5;
}

:deep(.description-content p) {
  margin-top: 0;
  margin-bottom: 12px;
}

:deep(.description-content h1),
:deep(.description-content h2),
:deep(.description-content h3),
:deep(.description-content h4),
:deep(.description-content h5),
:deep(.description-content h6) {
  font-weight: bold;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  line-height: 1.2;
}

:deep(.description-content h4) {
  font-size: 1.15em;
  color: var(--ion-color-primary, #3880ff);
}

:deep(.description-content ul), :deep(.description-content ol) {
  margin-top: 0;
  margin-bottom: 16px;
  padding-left: 24px;
}
:deep(.description-content li) {
  margin-bottom: 6px;
}

/* Contournement du Shadow DOM d'Ionic pour forcer le multiligne */
.multiline-large-title {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.2;
  display: block;
  width: 100%;
  padding-bottom: 8px; /* Évite que le texte touche le bas lors du scroll */
}
</style>
