<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Messages</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          v-model="searchQuery"
          placeholder="Rechercher un message..."
          animated
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <!-- État de chargement (Silent Refresh) -->
      <div v-if="isLoading && messages.length === 0" class="ion-text-center ion-padding">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Chargement des messages...</p>
      </div>

      <!-- Liste des messages -->
      <ion-list v-else-if="filteredMessages.length > 0">
        <ion-item 
          v-for="message in filteredMessages" 
          :key="message.id" 
          button 
          @click="viewMessage(message)"
        >
          <ion-label>
            <h2 v-html="message.title.rendered"></h2>
            <p>{{ formatMessageDate(message.date) }}</p>
          </ion-label>
        </ion-item>
      </ion-list>

      <!-- Aucun résultat -->
      <div v-else class="ion-text-center ion-padding">
        <p v-if="searchQuery">Aucun message trouvé pour "{{ searchQuery }}".</p>
        <p v-else>Aucun message disponible.</p>
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
  onIonViewWillEnter
} from '@ionic/vue';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useMessageStore, type Message } from '../stores/messages';
import { storeToRefs } from 'pinia';

const router = useRouter();
const messageStore = useMessageStore();
const { messages, isLoading } = storeToRefs(messageStore);

const searchQuery = ref('');

/**
 * Supprime les accents d'une chaîne
 */
const removeAccents = (str: string): string => {
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

/**
 * Formate la date de publication
 */
const formatMessageDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return `Le ${date.toLocaleDateString('fr-FR')}`;
};

/**
 * Filtrage local (Recherche textuelle sur le titre)
 */
const filteredMessages = computed(() => {
  if (!searchQuery.value.trim()) return messages.value;
  
  const query = removeAccents(searchQuery.value.toLowerCase());
  return messages.value.filter(m => 
    removeAccents(m.title.rendered.toLowerCase()).includes(query)
  );
});

/**
 * Action: Voir le message
 */
const viewMessage = (message: Message) => {
  router.push('/tabs/admin/message/' + message.id);
};

// Chargement des données au montage/entrée
onIonViewWillEnter(() => {
  messageStore.fetchMessages();
});
</script>

<style scoped>
ion-list { margin-top: 8px; }
h2 { font-weight: bold; }
</style>
