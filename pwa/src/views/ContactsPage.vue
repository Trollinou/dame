<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Contacts</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher un contact..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
      <ion-toolbar>
        <ion-grid class="ion-no-padding">
          <ion-row>
            <ion-col size="4">
              <ion-item lines="none" class="selector-item">
                <ion-select
                  v-model="selectedType"
                  interface="action-sheet"
                  label="Type"
                  label-placement="stacked"
                  class="custom-select"
                >
                  <ion-select-option value="all">Tous</ion-select-option>
                  <ion-select-option
                    v-for="type in contactTypes"
                    :key="type.id"
                    :value="type.id"
                  >
                    {{ type.name }}
                  </ion-select-option>
                </ion-select>
              </ion-item>
            </ion-col>
            <ion-col size="4">
              <ion-item lines="none" class="selector-item">
                <ion-select
                  v-model="selectedRegion"
                  interface="action-sheet"
                  label="Région"
                  label-placement="stacked"
                  class="custom-select"
                >
                  <ion-select-option value="all">Toutes</ion-select-option>
                  <ion-select-option
                    v-for="region in regions"
                    :key="region.code"
                    :value="region.code"
                  >
                    {{ region.name }}
                  </ion-select-option>
                </ion-select>
              </ion-item>
            </ion-col>
            <ion-col size="4">
              <ion-item lines="none" class="selector-item">
                <ion-select
                  v-model="selectedDepartment"
                  interface="action-sheet"
                  label="Dept."
                  label-placement="stacked"
                  class="custom-select"
                >
                  <ion-select-option value="all">Tous</ion-select-option>
                  <ion-select-option
                    v-for="dept in filteredDepartmentsList"
                    :key="dept.code"
                    :value="dept.code"
                  >
                    {{ dept.name }}
                  </ion-select-option>
                </ion-select>
              </ion-item>
            </ion-col>
          </ion-row>
        </ion-grid>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <div v-if="isLoading && contacts.length === 0" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des contacts...</p>
        </div>

        <ion-list v-else-if="filteredContacts.length > 0">
          <ion-item 
            v-for="contact in filteredContacts" 
            :key="contact.id" 
            :id="'contact-' + contact.id"
            button 
            @click="goToDetail(contact.id)"
          >
            <ion-label>
              <h2 v-safe-html="contact.title.rendered"></h2>
            </ion-label>
          </ion-item>
        </ion-list>

        <div v-else class="ion-text-center ion-padding">
          <p v-if="searchQuery">Aucun contact ne correspond à "{{ searchQuery }}".</p>
          <p v-else>Aucun contact trouvé.</p>
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
  IonSelect,
  IonSelectOption,
  IonGrid,
  IonRow,
  IonCol,
  onIonViewWillEnter,
  onIonViewDidEnter
} from '@ionic/vue';
import { ref, computed, nextTick, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useContactStore } from '../stores/contacts';
import { useReferenceDataStore } from '../stores/referenceData';
import { storeToRefs } from 'pinia';

const router = useRouter();
const contactStore = useContactStore();
const referenceDataStore = useReferenceDataStore();
const { contacts, contactTypes, isLoading } = storeToRefs(contactStore);
const { regions, departments, deptRegionMap } = storeToRefs(referenceDataStore);

const searchQuery = ref('');
const selectedType = ref<number | 'all'>('all');
const selectedRegion = ref<string | 'all'>('all');
const selectedDepartment = ref<string | 'all'>('all');
const lastViewedContactId = ref<number | null>(null);

/**
 * Départements filtrés par la région sélectionnée
 */
const filteredDepartmentsList = computed(() => {
  if (selectedRegion.value === 'all') return departments.value;
  return departments.value.filter(dept => deptRegionMap.value[dept.code] === selectedRegion.value);
});

/**
 * Surveillance du changement de région : on réinitialise le département s'il n'est plus valide
 */
watch(selectedRegion, (newRegion) => {
  if (newRegion !== 'all' && selectedDepartment.value !== 'all') {
    const regionOfDept = deptRegionMap.value[selectedDepartment.value];
    if (regionOfDept !== newRegion) {
      selectedDepartment.value = 'all';
    }
  }
});

/**
 * Surveillance du changement de département : on sélectionne la région correspondante
 */
watch(selectedDepartment, (newDept) => {
  if (newDept !== 'all') {
    const regionOfDept = deptRegionMap.value[newDept];
    if (regionOfDept && selectedRegion.value !== regionOfDept) {
      selectedRegion.value = regionOfDept;
    }
  }
});

const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

const goToDetail = (id: number) => {
  lastViewedContactId.value = id;
  router.push('/tabs/admin/contact/' + id);
};

const scrollToTarget = () => {
  if (lastViewedContactId.value) {
    const el = document.getElementById('contact-' + lastViewedContactId.value);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
};

const filteredContacts = computed(() => {
  let result = contacts.value;

  // Filtrage par type
  if (selectedType.value !== 'all') {
    result = result.filter(contact => 
      contact['contact-types'] && contact['contact-types'].includes(selectedType.value as number)
    );
  }

  // Filtrage par région
  if (selectedRegion.value !== 'all') {
    result = result.filter(contact => 
      contact.meta?._dame_contact_region === selectedRegion.value
    );
  }

  // Filtrage par département
  if (selectedDepartment.value !== 'all') {
    result = result.filter(contact => 
      contact.meta?._dame_contact_department === selectedDepartment.value
    );
  }

  // Filtrage par recherche
  if (searchQuery.value.trim()) {
    const query = removeAccents(searchQuery.value.toLowerCase());
    result = result.filter(contact => {
      const name = removeAccents((contact.title.rendered || "").toLowerCase());
      return name.includes(query);
    });
  }

  return result;
});

onIonViewWillEnter(async () => {
  contactStore.fetchContacts();
  contactStore.fetchContactTypes();
  referenceDataStore.fetchRegions();
  referenceDataStore.fetchDepartments();
  referenceDataStore.fetchMapping();
  await nextTick();
  setTimeout(scrollToTarget, 200);
});

onIonViewDidEnter(() => {
  if (!isLoading.value && contacts.value.length > 0) {
    setTimeout(scrollToTarget, 100);
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
  font-size: 0.85rem;
}

ion-list { margin-top: 8px; }
</style>
