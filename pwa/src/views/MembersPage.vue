<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Adhérents</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher un adhérent..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
      <ion-toolbar>
        <ion-item lines="none">
          <ion-select
            v-model="selectedSeason"
            interface="action-sheet"
            label="Saison"
            label-placement="start"
          >
            <ion-select-option value="all">Toutes les saisons</ion-select-option>
            <ion-select-option
              v-for="season in seasons"
              :key="season.id"
              :value="season.id"
            >
              {{ season.name }}
            </ion-select-option>
          </ion-select>
        </ion-item>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <!-- État de chargement -->
      <div v-if="isLoading && members.length === 0" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Récupération de la liste...</p>
      </div>

      <!-- Liste des membres -->
      <ion-list v-else-if="filteredMembers.length > 0">
        <ion-item 
          v-for="member in filteredMembers" 
          :key="member.id" 
          button 
          @click="goToDetail(member.id)"
        >
          <ion-label>
            <h2>{{ member.title.raw }}</h2>
          </ion-label>
        </ion-item>
      </ion-list>

      <!-- Aucun résultat -->
      <div v-else class="ion-text-center ion-padding">
        <p v-if="searchQuery">Aucun adhérent ne correspond à "{{ searchQuery }}".</p>
        <p v-else>Aucun adhérent trouvé.</p>
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
  IonItem,
  IonLabel,
  IonSpinner,
  IonSelect,
  IonSelectOption,
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useMemberStore } from '../stores/members';
import { storeToRefs } from 'pinia';

const router = useRouter();
const memberStore = useMemberStore();
const { members, seasons, isLoading } = storeToRefs(memberStore);
const searchQuery = ref('');
const selectedSeason = ref<number | 'all'>('all');

const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

const goToDetail = (id: number) => {
  router.push('/tabs/admin/members/' + id);
};

const filteredMembers = computed(() => {
  let result = members.value;
  if (selectedSeason.value !== 'all') {
    result = result.filter(member => 
      member.seasons && member.seasons.includes(selectedSeason.value as number)
    );
  }
  if (!searchQuery.value.trim()) {
    return result;
  }
  const query = removeAccents(searchQuery.value.toLowerCase());
  return result.filter(member => {
    const memberName = removeAccents((member.title.raw || "").toLowerCase());
    return memberName.includes(query);
  });
});

onIonViewWillEnter(async () => {
  memberStore.fetchMembers();
  await memberStore.fetchSeasons();
  if (selectedSeason.value === 'all' && seasons.value.length > 0) {
    selectedSeason.value = seasons.value[0].id;
  }
});
</script>

<style scoped>
ion-list { margin-top: 8px; }
</style>
