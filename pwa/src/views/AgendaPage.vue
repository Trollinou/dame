<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>{{ pageTitle }}</ion-title>
      </ion-toolbar>
      
      <!-- Sous-navigation (Segment) -->
      <ion-toolbar>
        <ion-segment :value="selectedSegment" @ionChange="onSegmentChange($event.detail.value as string)">
          <ion-segment-button value="actualites">
            <ion-label>Actualités</ion-label>
          </ion-segment-button>
          <ion-segment-button value="agenda">
            <ion-label>Agenda</ion-label>
          </ion-segment-button>
          <ion-segment-button value="tournois">
            <ion-label>Tournois</ion-label>
          </ion-segment-button>
          <ion-segment-button value="benevolat">
            <ion-label>Bénévolat</ion-label>
          </ion-segment-button>
        </ion-segment>
      </ion-toolbar>

      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          :placeholder="searchPlaceholder"
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" ref="contentRef" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">{{ pageTitle }}</ion-title>
          </ion-toolbar>
        </ion-header>

        <!-- ================= ONGLET 0 : ACTUALITES ================= -->
        <div v-if="selectedSegment === 'actualites'">
          <div v-if="newsStore.isLoading && newsStore.posts.length === 0" class="ion-text-center ion-padding">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement des actualités...</p>
          </div>
          
          <div v-else-if="filteredNews.length > 0">
            <ion-card 
              v-for="post in filteredNews" 
              :key="post.id" 
              class="news-card ion-no-margin ion-margin-bottom" 
              button 
              @click="goToNewsDetail(post.id)"
            >
              <img 
                v-if="getFeaturedImage(post)" 
                :src="getFeaturedImage(post) || undefined" 
                :alt="post.title.rendered"
                class="featured-image"
                style="width: 100%; height: 200px; object-fit: cover;"
              />
              <ion-card-header>
                <ion-card-subtitle>{{ formatDate(post.date) }}</ion-card-subtitle>
                <ion-card-title v-safe-html="post.title.rendered"></ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div v-safe-html="post.excerpt.rendered"></div>
              </ion-card-content>
            </ion-card>
          </div>

          <div v-else class="ion-text-center ion-padding">
            <p v-if="searchQuery">Aucune actualité ne correspond à "{{ searchQuery }}".</p>
            <p v-else>Aucune actualité trouvée.</p>
          </div>
        </div>

        <!-- ================= ONGLET 1 : AGENDA ================= -->
        <div v-if="selectedSegment === 'agenda'">
          <!-- Infinite Scroll TOP (Historique) -->
          <ion-infinite-scroll 
            v-if="!searchQuery"
            position="top" 
            @ionInfinite="loadMorePast($event)" 
            :disabled="!hasMorePast || isLoading"
          >
            <ion-infinite-scroll-content 
              loading-spinner="dots" 
              loading-text="Chargement de l'historique..."
            >
            </ion-infinite-scroll-content>
          </ion-infinite-scroll>

          <!-- État de chargement initial -->
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
                <h2 :class="{ 'upcoming-title': !isPast(event) }" v-safe-html="event.title.rendered"></h2>
                <p>{{ formatEventDate(event) }}</p>
              </ion-label>
              <ion-badge v-if="isToday(event)" color="warning" slot="end">Actuellement</ion-badge>
            </ion-item>
          </ion-list>

          <!-- Aucun résultat -->
          <div v-else class="ion-text-center ion-padding">
            <p v-if="searchQuery">Aucun événement ne correspond à "{{ searchQuery }}".</p>
            <p v-else>Aucun événement trouvé.</p>
          </div>

          <!-- Infinite Scroll BOTTOM (Futur) -->
          <ion-infinite-scroll 
            v-if="!searchQuery"
            @ionInfinite="loadMoreUpcoming($event)" 
            :disabled="!hasMoreUpcoming || isLoading"
          >
            <ion-infinite-scroll-content 
              loading-spinner="dots" 
              loading-text="Chargement des événements futurs..."
            >
            </ion-infinite-scroll-content>
          </ion-infinite-scroll>
        </div>

        <!-- ================= ONGLET 2 : TOURNOIS ================= -->
        <div v-else-if="selectedSegment === 'tournois'">
          <div v-if="tournamentStore.isLoading" class="ion-text-center ion-padding">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement des tournois...</p>
          </div>

          <div v-else-if="tournamentError && filteredTournaments.length === 0" class="ion-text-center ion-padding">
            <ion-icon :icon="cloudOfflineOutline" size="large" color="medium"></ion-icon>
            <p class="ion-margin-top">{{ tournamentError }}</p>
            <ion-button fill="solid" class="ion-margin-top" @click="fetchTournaments">Réessayer</ion-button>
          </div>

          <div v-else>
            <div v-if="tournamentError && filteredTournaments.length > 0" class="offline-banner ion-margin-bottom">
              <ion-icon :icon="cloudOfflineOutline"></ion-icon>
              <span>Mode hors-ligne : Affichage des données en cache</span>
            </div>

            <ion-card 
              v-for="item in filteredTournaments" 
              :key="item.id" 
              class="tournament-card ion-no-margin ion-margin-bottom" 
              button 
              @click="goToTournamentDetail(item.object_id)"
            >
              <ion-card-header>
                <div class="card-icon-container">
                  <ion-icon :icon="trophyOutline" color="primary"></ion-icon>
                </div>
                <ion-card-subtitle>Compétition</ion-card-subtitle>
                <ion-card-title v-safe-html="item.title"></ion-card-title>
              </ion-card-header>

              <ion-card-content>
                <p>Découvrez les détails, les horaires et les modalités d'inscription pour ce tournoi.</p>
                <div class="cta-container">
                  <span class="cta-text">Voir les détails</span>
                  <ion-icon :icon="chevronForwardOutline" size="small"></ion-icon>
                </div>
              </ion-card-content>
            </ion-card>

            <div v-if="filteredTournaments.length === 0 && !tournamentStore.isLoading" class="ion-text-center ion-padding">
              <p v-if="searchQuery">Aucun tournoi ne correspond à "{{ searchQuery }}".</p>
              <p v-else>Aucun tournoi disponible pour le moment.</p>
            </div>
          </div>
        </div>

        <!-- ================= ONGLET 3 : BENEVOLAT ================= -->
        <div v-else-if="selectedSegment === 'benevolat'">
          <div v-if="benevolatStore.isLoading" class="ion-text-center ion-padding">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement des appels à bénévoles...</p>
          </div>

          <div v-else>
            <!-- SECTION : APPELS EN COURS -->
            <ion-list v-if="openBenevolats.length > 0" lines="full">
              <ion-list-header>
                <ion-label color="primary">Appels en cours</ion-label>
              </ion-list-header>
              
              <ion-item v-for="benevolat in openBenevolats" :key="benevolat.id" button @click="viewBenevolat(benevolat)">
                <ion-icon slot="start" :icon="handRightOutline" color="primary" class="ion-margin-end"></ion-icon>
                <ion-label>
                  <h2 v-safe-html="benevolat.title.rendered"></h2>
                  <p>{{ formatBenevolatDates(benevolat) }}</p>
                </ion-label>
                <div slot="end" style="display: flex; align-items: center; gap: 8px;">
                  <ion-badge 
                    v-if="benevolatStore.hasUserVoted(benevolat.id) && !authStore.adminMode" 
                    color="success"
                  >
                    Inscrit
                  </ion-badge>
                  <ion-badge 
                    v-if="authStore.adminMode"
                    color="primary"
                  >
                    {{ benevolatStore.getResponseCount(benevolat.id) }} rép.
                  </ion-badge>
                </div>
              </ion-item>
            </ion-list>

            <!-- SECTION : APPELS TERMINÉS -->
            <ion-list v-if="finishedBenevolats.length > 0" lines="full" class="ion-margin-top">
              <ion-list-header>
                <ion-label color="medium">Appels terminés</ion-label>
              </ion-list-header>
              
              <ion-item v-for="benevolat in finishedBenevolats" :key="benevolat.id" button class="finished-item" @click="viewBenevolat(benevolat)">
                <ion-icon slot="start" :icon="handRightOutline" color="medium" class="ion-margin-end" style="opacity: 0.6;"></ion-icon>
                <ion-label>
                  <h2 v-safe-html="benevolat.title.rendered"></h2>
                  <p>{{ formatBenevolatDates(benevolat) }}</p>
                </ion-label>
                <div slot="end" style="display: flex; align-items: center; gap: 8px;">
                  <ion-badge 
                    v-if="benevolatStore.hasUserVoted(benevolat.id) && !authStore.adminMode" 
                    color="success"
                    style="opacity: 0.7;"
                  >
                    Inscrit
                  </ion-badge>
                  <ion-badge 
                    v-if="authStore.adminMode"
                    color="medium"
                  >
                    {{ benevolatStore.getResponseCount(benevolat.id) }} rép.
                  </ion-badge>
                </div>
              </ion-item>
            </ion-list>

            <div v-if="openBenevolats.length === 0 && finishedBenevolats.length === 0 && !benevolatStore.isLoading" class="ion-text-center ion-padding">
              <p v-if="searchQuery">Aucun appel ne correspond à "{{ searchQuery }}".</p>
              <p v-else>Aucun appel à bénévoles disponible.</p>
            </div>
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
  IonSearchbar,
  IonList,
  IonListHeader,
  IonItem,
  IonLabel,
  IonSpinner,
  IonBadge,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  IonSegment,
  IonSegmentButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonIcon,
  IonButton,
  onIonViewWillEnter
} from '@ionic/vue';
import { 
  trophyOutline, 
  chevronForwardOutline, 
  cloudOfflineOutline, 
  handRightOutline 
} from 'ionicons/icons';
import { ref, computed, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAgendaStore, type AgendaEvent } from '../stores/agenda';
import { useTournamentStore } from '../stores/tournament';
import { useBenevolatStore, type Benevolat } from '../stores/benevolat';
import { useAuthStore } from '../stores/auth';
import { useNewsStore, type Post } from '../stores/news';
import { storeToRefs } from 'pinia';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const agendaStore = useAgendaStore();
const tournamentStore = useTournamentStore();
const benevolatStore = useBenevolatStore();
const newsStore = useNewsStore();

const { events, isLoading, hasMoreUpcoming, hasMorePast, upcomingPage, pastPage } = storeToRefs(agendaStore);

// Gestion de la sous-navigation
const selectedSegment = ref('actualites');
const searchQuery = ref('');
const todayStr = agendaStore.getTodayLocal();
const isFirstLoad = ref(true);
const tournamentError = ref<string | null>(null);

const pageTitle = computed(() => {
  if (selectedSegment.value === 'actualites') return 'Actualités';
  if (selectedSegment.value === 'agenda') return 'Agenda';
  if (selectedSegment.value === 'tournois') return 'Tournois';
  if (selectedSegment.value === 'benevolat') return 'Bénévolat';
  return 'Le Club';
});

const searchPlaceholder = computed(() => {
  if (selectedSegment.value === 'tournois') return 'Rechercher un tournoi...';
  if (selectedSegment.value === 'benevolat') return 'Rechercher un appel...';
  if (selectedSegment.value === 'actualites') return 'Rechercher une actualité...';
  return 'Rechercher un événement...';
});

// Réinitialiser la recherche au changement d'onglet
const onSegmentChange = (val: string) => {
  selectedSegment.value = val;
  searchQuery.value = '';
  loadTabContent();
};

const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

const goToDetail = (id: number) => {
  router.push('/agenda/' + id);
};

const goToTournamentDetail = (id: number) => {
  router.push(`/page/${id}`);
};

const goToNewsDetail = (id: number) => {
  router.push('/news/' + id);
};

const viewBenevolat = (benevolat: Benevolat) => {
  if (authStore.adminMode) {
    router.push('/admin/benevolat/' + benevolat.id);
  } else {
    if (authStore.isAuthenticated) {
      router.push('/benevolat/participation/' + benevolat.id);
    } else {
      router.push({ 
        path: '/login', 
        query: { message: 'Identification requise pour proposer votre aide.' } 
      });
    }
  }
};

const formatPart = (dateString: string): string => {
  if (!dateString) return '';
  return dateString.split('-').reverse().join('/');
};

const isPast = (event: AgendaEvent): boolean => {
  const today = agendaStore.getTodayLocal();
  const referenceDate = event.meta?._dame_end_date || event.meta?._dame_start_date || '';
  return referenceDate < today;
};

const isToday = (event: AgendaEvent): boolean => {
  const startDate = event.meta?._dame_start_date;
  const endDate = event.meta?._dame_end_date;

  if (!startDate) return false;

  if (endDate && startDate !== endDate) {
    return todayStr >= startDate && todayStr <= endDate;
  }

  return startDate === todayStr;
};

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

// Chargeur dynamique du contenu de l'onglet actif
const fetchNews = async () => {
  try {
    await newsStore.fetchPosts();
  } catch (err) {
    console.warn("Erreur chargement posts:", err);
  }
};

const fetchTournaments = async () => {
  tournamentError.value = null;
  try {
    await tournamentStore.fetchMenu();
  } catch {
    if (!navigator.onLine) {
      tournamentError.value = "Vous êtes hors-ligne. Les informations sur les tournois nécessitent une connexion.";
    } else {
      tournamentError.value = "Impossible de charger les tournois.";
    }
  }
};

const fetchBenevolats = async () => {
  try {
    await benevolatStore.fetchBenevolatsData();
  } catch (err) {
    console.error("Erreur chargement bénévolat:", err);
  }
};

const loadTabContent = () => {
  if (selectedSegment.value === 'actualites') {
    fetchNews();
  } else if (selectedSegment.value === 'tournois') {
    fetchTournaments();
  } else if (selectedSegment.value === 'benevolat') {
    fetchBenevolats();
  }
};

// ================= CODE FILTRAGE DES ONGLETS =================

// FILTRAGE ACTUALITES
const filteredNews = computed(() => {
  if (!searchQuery.value.trim()) return newsStore.posts;
  const query = removeAccents(searchQuery.value.toLowerCase());
  return newsStore.posts.filter((post: Post) => 
    removeAccents((post.title?.rendered || "").toLowerCase()).includes(query)
  );
});

const getFeaturedImage = (post: Post): string | null => {
  return post._embedded?.['wp:featuredmedia']?.[0]?.source_url || null;
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

// FILTRAGE AGENDA
const filteredEvents = computed(() => {
  if (!searchQuery.value.trim()) return events.value;
  const query = removeAccents(searchQuery.value.toLowerCase());
  return events.value.filter(event => 
    removeAccents((event.title?.raw || "").toLowerCase()).includes(query)
  );
});

// FILTRAGE TOURNOIS
const filteredTournaments = computed(() => {
  const topLevel = tournamentStore.menuItems.filter(item => String(item.parent) === "0");
  if (!searchQuery.value.trim()) return topLevel;
  const query = removeAccents(searchQuery.value.toLowerCase());
  return topLevel.filter(item => 
    removeAccents((item.title || "").toLowerCase()).includes(query)
  );
});

// FILTRAGE BENEVOLAT
const isBenevolatExpired = (benevolat: Benevolat): boolean => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const dates = data.map(d => d.date).filter(Boolean);
    if (dates.length === 0) return false;
    const maxDate = dates.reduce((max, d) => d > max ? d : max, dates[0]);
    return maxDate < todayStr;
  }
  return false;
};

const formatBenevolatDates = (benevolat: Benevolat): string => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const firstDate = data[0].date;
    const lastDate = data[data.length - 1].date;
    const format = (d: string) => d ? d.split('-').reverse().join('/') : '?';
    return `Du ${format(firstDate)} au ${format(lastDate)}`;
  }
  return 'Dates non définies';
};

const sortedBenevolats = computed(() => {
  let list = benevolatStore.benevolats;
  if (searchQuery.value.trim()) {
    const query = removeAccents(searchQuery.value.toLowerCase());
    list = list.filter(b => 
      removeAccents(b.title.rendered.toLowerCase()).includes(query)
    );
  }
  return [...list].sort((a, b) => {
    const dateA = a.dame_benevolat_data?.[0]?.date || '';
    const dateB = b.dame_benevolat_data?.[0]?.date || '';
    return dateA.localeCompare(dateB);
  });
});

const openBenevolats = computed(() => {
  return sortedBenevolats.value.filter(b => !isBenevolatExpired(b));
});

const finishedBenevolats = computed(() => {
  const list = sortedBenevolats.value.filter(b => isBenevolatExpired(b));
  return [...list].sort((a, b) => {
    const datesA = a.dame_benevolat_data?.map(d => d.date).filter(Boolean) || [];
    const datesB = b.dame_benevolat_data?.map(d => d.date).filter(Boolean) || [];
    const maxDateA = datesA.reduce((max, d) => d > max ? d : max, '');
    const maxDateB = datesB.reduce((max, d) => d > max ? d : max, '');
    return maxDateB.localeCompare(maxDateA);
  });
});

// ================= FIN CODE ONGLETS =================

const loadMoreUpcoming = async (ev: any) => {
  upcomingPage.value++;
  const data = await agendaStore.fetchBatch('upcoming', todayStr, upcomingPage.value);
  if (data && data.length > 0) {
    const newItems = data.filter(newItem => !events.value.some(existing => existing.id === newItem.id));
    events.value = [...events.value, ...newItems];
  }
  ev.target.complete();
};

const loadMorePast = async (ev: any) => {
  const data = await agendaStore.fetchBatch('past', todayStr, pastPage.value);
  if (data && data.length > 0) {
    const dataAsc = [...data].reverse();
    const newItems = dataAsc.filter(newItem => !events.value.some(existing => existing.id === newItem.id));
    events.value = [...newItems, ...events.value];
    pastPage.value++;
  }
  ev.target.complete();
};

onIonViewWillEnter(async () => {
  if (route.query.tab) {
    selectedSegment.value = route.query.tab as string;
  }
  loadTabContent();

  const hasPast = events.value.some(e => isPast(e));
  
  if (events.value.length === 0 || !hasPast) {
    isLoading.value = true;
    
    if (events.value.length === 0) {
      const [upcomingData, pastData] = await Promise.all([
        agendaStore.fetchBatch('upcoming', todayStr, 1),
        agendaStore.fetchBatch('past', todayStr, 1)
      ]);
      
      let merged: AgendaEvent[] = [];
      if (pastData && pastData.length > 0) {
        merged = [...pastData].reverse();
        pastPage.value = 2;
      }
      
      if (upcomingData && upcomingData.length > 0) {
        merged = [...merged, ...upcomingData];
        upcomingPage.value = 2;
      }
      
      events.value = merged;
    } else {
      const pastData = await agendaStore.fetchBatch('past', todayStr, 1);
      if (pastData && pastData.length > 0) {
        const merged = [...pastData].reverse();
        const newItems = merged.filter(newItem => !events.value.some(existing => existing.id === newItem.id));
        events.value = [...newItems, ...events.value];
        pastPage.value = 2;
      }
    }
    
    isLoading.value = false;
  }
  
  if (isFirstLoad.value) {
    await nextTick();
    setTimeout(() => {
      const targetEvent = events.value.find(e => (e.meta?._dame_start_date || '') >= todayStr);
      if (targetEvent) {
        const el = document.getElementById('event-' + targetEvent.id);
        if (el) {
          el.scrollIntoView({ behavior: 'auto', block: 'center' });
        }
      }
      isFirstLoad.value = false;
    }, 200);
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

ion-list { margin-top: 8px; }
h2 { font-weight: bold; }
p { color: var(--ion-color-medium); }
.past-event { opacity: 0.6; }
.past-event h2 { font-weight: normal; }
.upcoming-title { color: var(--ion-color-primary); }
ion-badge { margin-left: 8px; }

/* Styles spécifiques pour les tournois sous-segment */
.tournament-card {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.card-icon-container {
  font-size: 2.5rem;
  margin-bottom: 10px;
}

ion-card-title {
  font-size: 1.2rem;
  font-weight: 700;
  line-height: 1.2;
}

ion-card-subtitle {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ion-color-medium);
}

.cta-container {
  display: flex;
  align-items: center;
  margin-top: 15px;
  color: var(--ion-color-primary);
  font-weight: 600;
}

.cta-text {
  margin-right: 5px;
}

.offline-banner {
  background: var(--ion-color-warning);
  color: var(--ion-color-warning-contrast);
  padding: 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.85rem;
  font-weight: 500;
}

ion-list-header { 
  --color: var(--ion-color-primary);
  font-weight: bold;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.85em;
  margin-bottom: 4px;
}

.finished-item {
  --opacity: 0.8;
}

.finished-item h2 {
  color: var(--ion-color-medium);
}
</style>
