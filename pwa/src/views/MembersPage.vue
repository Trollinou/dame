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
        <ion-grid class="ion-no-padding">
          <ion-row>
            <ion-col size="6">
              <ion-item lines="none" class="selector-item">
                <ion-select
                  v-model="selectedSeason"
                  interface="action-sheet"
                  label="Saison"
                  label-placement="stacked"
                  class="custom-select"
                >
                  <ion-select-option value="all">Toutes</ion-select-option>
                  <ion-select-option
                    v-for="season in seasons"
                    :key="season.id"
                    :value="season.id"
                  >
                    {{ season.name }}
                  </ion-select-option>
                </ion-select>
              </ion-item>
            </ion-col>
            <ion-col size="6">
              <ion-item lines="none" class="selector-item">
                <ion-select
                  v-model="sortBy"
                  interface="action-sheet"
                  label="Tri"
                  label-placement="stacked"
                  class="custom-select"
                >
                  <ion-select-option value="name_asc">Nom A-Z</ion-select-option>
                  <ion-select-option value="name_desc">Nom Z-A</ion-select-option>
                  <ion-select-option value="age_category">Catégorie</ion-select-option>
                </ion-select>
              </ion-item>
            </ion-col>
          </ion-row>
        </ion-grid>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
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
              <h2 v-safe-html="member.title.rendered"></h2>
            </ion-label>
            <ion-badge v-if="member.dame_age_category" slot="end" color="light">
              {{ member.dame_age_category }}
            </ion-badge>
          </ion-item>
        </ion-list>

        <!-- Aucun résultat -->
        <div v-else class="ion-text-center ion-padding">
          <p v-if="searchQuery">Aucun adhérent ne correspond à "{{ searchQuery }}".</p>
          <p v-else>Aucun adhérent trouvé.</p>
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
  IonItem,
  IonLabel,
  IonSpinner,
  IonBadge,
  IonSelect,
  IonSelectOption,
  IonGrid,
  IonRow,
  IonCol,
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
const sortBy = ref<'name_asc' | 'name_desc' | 'age_category'>('name_asc');

const AGE_CATEGORY_ORDER = [
  'U8', 'U8F', 'U10', 'U10F', 'U12', 'U12F', 'U14', 'U14F', 'U16', 'U16F', 
  'U18', 'U18F', 'U20', 'U20F', 'Sénior', 'SéniorF', 'Sénior+', 'Sénior+F', 'Vétéran', 'VétéranF'
];

const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

const goToDetail = (id: number) => {
  router.push('/tabs/admin/members/' + id);
};

const filteredMembers = computed(() => {
  // On travaille sur une copie pour le tri
  let result = [...members.value];
  
  // 1. Filtrage par saison
  if (selectedSeason.value !== 'all') {
    result = result.filter(member => 
      member.seasons && member.seasons.includes(selectedSeason.value as number)
    );
  }

  // 2. Filtrage par recherche
  if (searchQuery.value.trim()) {
    const query = removeAccents(searchQuery.value.toLowerCase());
    result = result.filter(member => {
      const memberName = removeAccents((member.title.rendered || "").toLowerCase());
      return memberName.includes(query);
    });
  }

  // 3. Tri
  result.sort((a, b) => {
    if (sortBy.value === 'age_category') {
      const catA = a.dame_age_category || '';
      const catB = b.dame_age_category || '';
      
      if (catA !== catB) {
        const indexA = AGE_CATEGORY_ORDER.indexOf(catA);
        const indexB = AGE_CATEGORY_ORDER.indexOf(catB);

        if (indexA !== -1 && indexB !== -1) return indexA - indexB;
        if (indexA === -1) return 1;
        if (indexB === -1) return -1;
        return catA.localeCompare(catB);
      }
      // Si catégories identiques, on tombe sur le tri par nom
    }

    const nameA = removeAccents((a.title.rendered || "").toLowerCase());
    const nameB = removeAccents((b.title.rendered || "").toLowerCase());
    const cmp = nameA.localeCompare(nameB);
    
    return sortBy.value === 'name_desc' ? -cmp : cmp;
  });

  return result;
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
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.selector-item {
  --padding-start: 8px;
  --padding-end: 8px;
  --min-height: 54px;
}

.custom-select {
  width: 100%;
  font-size: 0.9rem;
}

ion-list { margin-top: 8px; }
</style>
