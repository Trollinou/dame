<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/admin/contact"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="contact" v-safe-html="contact.title.rendered"></ion-title>
        <ion-title v-else>Détails Contact</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">
              <div class="multiline-large-title" v-if="contact" v-safe-html="contact.title.rendered"></div>
              <div class="multiline-large-title" v-else>Détails Contact</div>
            </ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="contact">
          <!-- Carte Identité -->
          <ion-card class="ion-no-margin ion-margin-bottom">
            <ion-card-header>
              <ion-card-title>
                <ion-icon :icon="personOutline" color="primary"></ion-icon>
                Identité
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div v-if="contact.meta?._dame_contact_organization" class="organization-name">
                {{ contact.meta._dame_contact_organization }}
              </div>
              <div class="contact-name">
                {{ contact.meta?._dame_contact_first_name }} {{ contact.meta?._dame_contact_last_name }}
              </div>
              <div v-if="contact.meta?._dame_contact_role" class="role-name">
                {{ contact.meta._dame_contact_role }}
              </div>
            </ion-card-content>
          </ion-card>

          <!-- Carte Coordonnées -->
          <ion-card class="ion-no-margin ion-margin-bottom">
            <ion-card-header>
              <ion-card-title>
                <ion-icon :icon="callOutline" color="primary"></ion-icon>
                Coordonnées
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-list lines="inset">
                <!-- Email -->
                <ion-item v-if="contact.meta?._dame_contact_email">
                  <ion-icon slot="start" :icon="mailOutline" color="primary"></ion-icon>
                  <ion-label>
                    <p>Email</p>
                    <h3>{{ contact.meta._dame_contact_email }}</h3>
                  </ion-label>
                  <ion-button 
                    slot="end" 
                    fill="clear" 
                    :href="'mailto:' + contact.meta._dame_contact_email"
                  >
                    <ion-icon slot="icon-only" :icon="sendOutline"></ion-icon>
                  </ion-button>
                </ion-item>
                
                <!-- Téléphone -->
                <ion-item v-if="contact.meta?._dame_contact_phone">
                  <ion-icon slot="start" :icon="callOutline" color="primary"></ion-icon>
                  <ion-label>
                    <p>Téléphone</p>
                    <h3>{{ contact.meta._dame_contact_phone }}</h3>
                  </ion-label>
                  <ion-button 
                    slot="end" 
                    fill="clear" 
                    :href="'tel:' + contact.meta._dame_contact_phone"
                  >
                    <ion-icon slot="icon-only" :icon="callOutline"></ion-icon>
                  </ion-button>
                </ion-item>

                <!-- Adresse -->
                <ion-item v-if="contact.meta?._dame_contact_address_1 || contact.meta?._dame_contact_city" lines="none">
                  <ion-icon slot="start" :icon="locationOutline" color="primary"></ion-icon>
                  <ion-label class="ion-text-wrap">
                    <p>Adresse postale</p>
                    <h3>{{ contact.meta?._dame_contact_address_1 }}</h3>
                    <h3 v-if="contact.meta?._dame_contact_address_2">{{ contact.meta?._dame_contact_address_2 }}</h3>
                    <h3>{{ contact.meta?._dame_contact_postcode }} {{ contact.meta?._dame_contact_city }}</h3>
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
        </div>

        <!-- Chargement ou Introuvable -->
        <div v-else class="ion-text-center ion-padding mt-large">
          <div v-if="contactStore.isLoading">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement du contact...</p>
          </div>
          <div v-else>
            <ion-icon :icon="personOutline" size="large" color="medium"></ion-icon>
            <h2>Contact introuvable</h2>
            <p>Ce contact n'existe pas ou la liste n'est pas encore chargée.</p>
            <ion-button expand="block" fill="outline" router-link="/tabs/contact" class="ion-margin-top">
              Retour à la liste
            </ion-button>
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
  mailOutline, 
  callOutline, 
  personOutline,
  sendOutline,
  locationOutline
} from 'ionicons/icons';
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useContactStore } from '@/stores/contacts';

const route = useRoute();
const contactStore = useContactStore();

const contactId = parseInt(route.params.id as string);

/**
 * Récupère le contact correspondant dans le store
 */
const contact = computed(() => {
  return contactStore.contacts.find(c => c.id === contactId);
});

/**
 * Génère l'URL de la carte selon la plateforme
 */
const mapUrl = computed(() => {
  if (!contact.value || !contact.value.meta) return '#';
  
  const m = contact.value.meta;
  const fullAddress = `${m._dame_contact_address_1 || ''} ${m._dame_contact_postcode || ''} ${m._dame_contact_city || ''}`.trim();
  
  if (!fullAddress) return '#';

  const encodedAddress = encodeURIComponent(fullAddress);

  if (isPlatform('ios')) {
    return `http://maps.apple.com/?q=${encodedAddress}`;
  } else if (isPlatform('android')) {
    return `geo:0,0?q=${encodedAddress}`;
  } else {
    return `https://www.google.com/maps/search/?api=1&query=${encodedAddress}`;
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

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

.multiline-large-title {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.2;
  display: block;
  width: 100%;
  padding-bottom: 8px;
}

.organization-name {
  font-size: 0.9em;
  color: var(--ion-color-medium);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.contact-name {
  font-size: 1.4em;
  font-weight: bold;
  margin: 4px 0;
  color: var(--ion-color-dark);
}

.role-name {
  font-size: 1em;
  color: var(--ion-color-primary);
  font-weight: 500;
}
</style>
