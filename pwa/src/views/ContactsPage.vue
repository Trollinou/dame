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

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Contacts</ion-title>
        </ion-toolbar>
      </ion-header>

      <!-- État de chargement (Spinner uniquement si la liste est vide) -->
      <div v-if="isLoading && contacts.length === 0" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Chargement des contacts...</p>
      </div>

      <!-- Liste des contacts -->
      <ion-list v-else-if="filteredContacts.length > 0">
        <ion-item v-for="contact in filteredContacts" :key="contact.id" button>
          <ion-label>
            <h2>{{ contact.title.raw }}</h2>
          </ion-label>
        </ion-item>
      </ion-list>

      <!-- Aucun résultat -->
      <div v-else class="ion-text-center ion-padding">
        <p v-if="searchQuery">Aucun contact ne correspond à "{{ searchQuery }}".</p>
        <p v-else>Aucun contact trouvé.</p>
      </div>

      <!-- Bouton Flottant (Ajouter) -->
      <ion-fab slot="fixed" vertical="bottom" horizontal="end">
        <ion-fab-button @click="addContact">
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
  IonSelect,
  IonSelectOption,
  onIonViewWillEnter
} from '@ionic/vue';
import { addOutline } from 'ionicons/icons';
import { ref, computed } from 'vue';
import { useContactStore } from '../stores/contacts';
import { storeToRefs } from 'pinia';

const contactStore = useContactStore();
const { contacts, contactTypes, isLoading } = storeToRefs(contactStore);
const searchQuery = ref('');
const selectedType = ref<number | 'all'>('all');

/**
 * Supprime les accents d'une chaîne de caractères
 */
const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

/**
 * Filtrage local des contacts (Type + Texte)
 */
const filteredContacts = computed(() => {
  let result = contacts.value;

  // 1. Filtre par Type
  if (selectedType.value !== 'all') {
    result = result.filter(contact => 
      contact['contact-types'] && contact['contact-types'].includes(selectedType.value as number)
    );
  }

  // 2. Filtre par Texte
  if (!searchQuery.value.trim()) {
    return result;
  }

  const query = removeAccents(searchQuery.value.toLowerCase());
  return result.filter(contact => {
    const name = removeAccents((contact.title.raw || "").toLowerCase());
    return name.includes(query);
  });
});

const addContact = () => {
  console.log("Ajouter un contact cliqué");
};

// Déclenche le fetch dans le store (Gestion silencieuse interne)
onIonViewWillEnter(() => {
  contactStore.fetchContacts();
  contactStore.fetchContactTypes();
});
</script>

<style scoped>
ion-list { margin-top: 8px; }
</style>
