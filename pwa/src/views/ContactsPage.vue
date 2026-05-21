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
        <ion-item lines="none">
          <ion-select
            v-model="selectedType"
            interface="action-sheet"
            label="Type"
            label-placement="start"
          >
            <ion-select-option value="all">Tous les types</ion-select-option>
            <ion-select-option
              v-for="type in contactTypes"
              :key="type.id"
              :value="type.id"
            >
              {{ type.name }}
            </ion-select-option>
          </ion-select>
        </ion-item>
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
              <h2 v-html="contact.title.rendered"></h2>
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
  onIonViewWillEnter,
  onIonViewDidEnter
} from '@ionic/vue';
import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useContactStore } from '../stores/contacts';
import { storeToRefs } from 'pinia';

const router = useRouter();
const contactStore = useContactStore();
const { contacts, contactTypes, isLoading } = storeToRefs(contactStore);
const searchQuery = ref('');
const selectedType = ref<number | 'all'>('all');
const lastViewedContactId = ref<number | null>(null);

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
  if (selectedType.value !== 'all') {
    result = result.filter(contact => 
      contact['contact-types'] && contact['contact-types'].includes(selectedType.value as number)
    );
  }
  if (!searchQuery.value.trim()) {
    return result;
  }
  const query = removeAccents(searchQuery.value.toLowerCase());
  return result.filter(contact => {
    const name = removeAccents((contact.title.rendered || "").toLowerCase());
    return name.includes(query);
  });
});

onIonViewWillEnter(async () => {
  contactStore.fetchContacts();
  contactStore.fetchContactTypes();
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

ion-list { margin-top: 8px; }
</style>
