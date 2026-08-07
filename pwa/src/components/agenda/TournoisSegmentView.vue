<template>
  <div>
    <div v-if="tournamentStore.isLoading" class="ion-text-center ion-padding">
      <ion-spinner name="crescent"></ion-spinner>
      <p>Chargement des tournois...</p>
    </div>

    <div v-else-if="tournamentError && filteredTournaments.length === 0" class="ion-text-center ion-padding">
      <ion-icon :icon="cloudOfflineOutline" size="large" color="medium"></ion-icon>
      <p class="ion-margin-top">{{ tournamentError }}</p>
      <ion-button fill="solid" class="ion-margin-top" @click="$emit('retry')">Réessayer</ion-button>
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
        @click="$emit('go-to-tournament-detail', item.object_id)"
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
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonIcon,
  IonButton
} from '@ionic/vue';
import { trophyOutline, chevronForwardOutline, cloudOfflineOutline } from 'ionicons/icons';
import { useTournamentStore } from '@/stores/tournament';
import { removeAccents } from '@/utils/stringUtils';

const tournamentStore = useTournamentStore();

const props = defineProps<{
  searchQuery: string;
  tournamentError: string | null;
}>();

defineEmits<{
  (e: 'go-to-tournament-detail', id: number): void;
  (e: 'retry'): void;
}>();

const filteredTournaments = computed(() => {
  const topLevel = tournamentStore.menuItems.filter((item) => String(item.parent) === '0');
  if (!props.searchQuery.trim()) return topLevel;
  const query = removeAccents(props.searchQuery.toLowerCase());
  return topLevel.filter((item) =>
    removeAccents((item.title || '').toLowerCase()).includes(query)
  );
});
</script>

<style scoped>
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
</style>
