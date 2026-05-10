<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Agenda</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher un événement..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" ref="contentRef">
      <!-- État de chargement (Spinner bloquant uniquement si vide) -->
      <div v-if="isLoading && events.length === 0" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Chargement de l'agenda...</p>
      </div>

      <!-- Liste des événements -->
      <ion-list v-else-if="filteredEvents.length > 0">
        <ion-item
          v-for="event in filteredEvents"
          :key="event.id"
          :id="'event-' + event.id"
          button
          @click="goToDetail(event.id)"
          :class="{ 'past-event': isPast(event) }"
        >
          <ion-label>
            <h2 :class="{ 'upcoming-title': !isPast(event) }" v-html="event.title.rendered"></h2>
            <p>{{ formatEventDate(event) }}</p>
          </ion-label>
          <ion-badge v-if="isToday(event)" color="warning" slot="end">Aujourd'hui</ion-badge>
        </ion-item>
      </ion-list>

      <!-- Aucun résultat -->
      <div v-else class="ion-text-center ion-padding">
        <p v-if="searchQuery">Aucun événement ne correspond à "{{ searchQuery }}".</p>
        <p v-else>Aucun événement trouvé.</p>
      </div>

      <!-- Bouton Flottant (Ajouter) -->
      <ion-fab slot="fixed" vertical="bottom" horizontal="end">
        <ion-fab-button @click="addEvent">
          <ion-icon :icon="addOutline"></ion-icon>
        </ion-fab-button>
      </ion-fab>
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
  IonSearchbar,
  IonList,
  IonItem,
  IonLabel,
  IonSpinner,
  IonFab,
  IonFabButton,
  IonIcon,
  IonBadge,
  onIonViewWillEnter,
  onIonViewDidEnter
} from '@ionic/vue';
import { addOutline } from 'ionicons/icons';
import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '../stores/agenda';
import { storeToRefs } from 'pinia';

const router = useRouter();
const agendaStore = useAgendaStore();
const { events, isLoading } = storeToRefs(agendaStore);

const contentRef = ref();
const searchQuery = ref('');
const lastViewedEventId = ref<number | null>(null); // Mémorise le dernier événement consulté
const todayStr = new Date().toISOString().split('T')[0];

/**
 * Supprime les accents d'une chaîne de caractères
 */
const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

/**
 * Navigation vers le détail d'un événement
 */
const goToDetail = (id: number) => {
  lastViewedEventId.value = id; // Sauvegarde l'ID avant de partir
  router.push('/tabs/agenda/' + id);
};

/**
 * Convertit une date YYYY-MM-DD en DD/MM/YYYY
 */
const formatPart = (dateString: string): string => {
  if (!dateString) return '';
  return dateString.split('-').reverse().join('/');
};

/**
 * Détermine si l'événement est passé
 */
const isPast = (event: AgendaEvent): boolean => {
  return (event.meta?._dame_start_date || '') < todayStr;
};

/**
 * Détermine si l'événement est aujourd'hui
 */
const isToday = (event: AgendaEvent): boolean => {
  return event.meta?._dame_start_date === todayStr;
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

/**
 * Défilement automatique vers la cible (Dernier consulté ou Aujourd'hui)
 */
const scrollToTarget = () => {
  let targetId = lastViewedEventId.value;

  // Si pas de dernier consulté, on cherche aujourd'hui ou le futur
  if (!targetId) {
    const upcomingEvent = events.value.find(event => 
      (event.meta?._dame_start_date || '') >= todayStr
    );
    if (upcomingEvent) targetId = upcomingEvent.id;
  }

  if (targetId) {
    const el = document.getElementById('event-' + targetId);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
  
  // On réinitialise pour le prochain changement d'onglet (reviendra à aujourd'hui par défaut)
  // mais on le garde pour le "back" immédiat.
  // En fait, ne pas le réinitialiser ici permet de rester dessus si on fait plusieurs va-et-vient.
};

/**
 * Filtrage local
 */
const filteredEvents = computed(() => {
  if (!searchQuery.value.trim()) {
    return events.value;
  }
  const query = removeAccents(searchQuery.value.toLowerCase());
  return events.value.filter(event => {
    const name = removeAccents((event.title?.raw || "").toLowerCase());
    return name.includes(query);
  });
});

const addEvent = () => {
  console.log("Ajouter un événement cliqué");
};

// Charger les données (Pinia Store)
onIonViewWillEnter(async () => {
  await agendaStore.fetchAgenda();
  // Une fois chargé, on tente un scroll au prochain cycle
  await nextTick();
  setTimeout(scrollToTarget, 200);
});

// Sécurité supplémentaire au cas où les données seraient déjà là
onIonViewDidEnter(() => {
  if (!isLoading.value && events.value.length > 0) {
    setTimeout(scrollToTarget, 100);
  }
});
</script>

<style scoped>
ion-list { margin-top: 8px; }
h2 { font-weight: bold; }
p { color: var(--ion-color-medium); }
.past-event { opacity: 0.6; }
.past-event h2 { font-weight: normal; }
.upcoming-title { color: var(--ion-color-primary); }
ion-badge { margin-left: 8px; }
</style>
