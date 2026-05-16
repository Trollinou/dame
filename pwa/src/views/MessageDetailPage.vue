<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/message"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="message" v-html="message.title.rendered"></ion-title>
        <ion-title v-else>Détails Message</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-segment v-model="currentTab">
          <ion-segment-button value="message">
            <ion-icon :icon="mailOutline"></ion-icon>
            <ion-label>Contenu</ion-label>
          </ion-segment-button>
          <ion-segment-button value="report">
            <ion-icon :icon="barChartOutline"></ion-icon>
            <ion-label>Rapport</ion-label>
          </ion-segment-button>
        </ion-segment>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">
            <div class="multiline-large-title" v-if="message" v-html="message.title.rendered"></div>
            <div class="multiline-large-title" v-else>Détails Message</div>
          </ion-title>
        </ion-toolbar>
      </ion-header>

      <div v-if="message">
        <!-- Onglet CONTENU -->
        <div v-if="currentTab === 'message'">
          <ion-card>
            <ion-card-content>
              <div class="description-content" v-html="message.content.rendered"></div>
            </ion-card-content>
          </ion-card>
        </div>

        <!-- Onglet RAPPORT -->
        <div v-if="currentTab === 'report'">
          <ion-card v-if="message.report">
            <ion-card-content class="stats-card">
              <div class="stat-item main-stat">
                <span class="stat-value">{{ message.report.stats.rate }}%</span>
                <span class="stat-label">Taux d'ouverture</span>
              </div>
              <div class="stats-grid">
                <div class="stat-item">
                  <span class="stat-value">{{ message.report.stats.sent }}</span>
                  <span class="stat-label">Envoyés</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value">{{ message.report.stats.opened }}</span>
                  <span class="stat-label">Ouverts</span>
                </div>
              </div>
            </ion-card-content>
          </ion-card>

          <ion-list v-if="message.report?.recipients?.length" class="ion-margin-top">
            <ion-list-header>
              <ion-label>Destinataires</ion-label>
            </ion-list-header>
            <ion-item v-for="(recipient, index) in message.report.recipients" :key="index">
              <ion-icon 
                slot="start" 
                :icon="getRecipientIcon(recipient)" 
                :color="getRecipientColor(recipient)"
              ></ion-icon>
              <ion-label>
                <h2>{{ recipient.name }}</h2>
                <p>{{ recipient.email }}</p>
              </ion-label>
              <ion-note slot="end" v-if="recipient.opened_at" class="ion-text-end">
                Ouvert le<br />{{ formatDateTime(recipient.opened_at) }}
              </ion-note>
              <ion-note slot="end" v-else-if="recipient.sent_at" class="ion-text-end">
                Envoyé le<br />{{ formatDateTime(recipient.sent_at) }}
              </ion-note>
            </ion-item>
          </ion-list>
          <div v-else class="ion-text-center ion-padding">
            <p>Aucun rapport disponible pour ce message.</p>
          </div>
        </div>
      </div>

      <!-- Chargement ou Introuvable -->
      <div v-else class="ion-text-center ion-padding mt-large">
        <div v-if="messageStore.isLoading">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement du message...</p>
        </div>
        <div v-else>
          <h2>Message introuvable</h2>
          <ion-button expand="block" fill="outline" router-link="/tabs/admin/message" class="ion-margin-top">
            Retour à la liste
          </ion-button>
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
  IonSegment,
  IonSegmentButton,
  IonIcon,
  IonLabel,
  IonCard,
  IonCardContent,
  IonList,
  IonListHeader,
  IonItem,
  IonNote,
  IonSpinner,
  IonButton
} from '@ionic/vue';
import { 
  mailOutline, 
  barChartOutline, 
  checkmarkCircle, 
  paperPlane, 
  timeOutline 
} from 'ionicons/icons';
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useMessageStore } from '@/stores/messages';

const route = useRoute();
const messageStore = useMessageStore();
const messageId = parseInt(route.params.id as string);

const currentTab = ref('message');

const message = computed(() => {
  return messageStore.messages.find(m => m.id === messageId);
});

/**
 * Formate une date ISO en DD/MM/YYYY HH:MM
 */
const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

/**
 * Retourne l'icône selon l'état du destinataire
 */
const getRecipientIcon = (recipient: any) => {
  if (recipient.opened_at) return checkmarkCircle;
  if (recipient.sent_at) return paperPlane;
  return timeOutline;
};

/**
 * Retourne la couleur selon l'état du destinataire
 */
const getRecipientColor = (recipient: any) => {
  if (recipient.opened_at) return 'success';
  if (recipient.sent_at) return 'medium';
  return 'warning';
};
</script>

<style scoped>
.mt-large {
  margin-top: 5%;
}

.multiline-large-title {
  white-space: normal !important;
  word-wrap: break-word;
  line-height: 1.2;
  display: block;
  width: 100%;
  padding-bottom: 8px;
}

.description-content {
  color: var(--ion-color-dark);
  line-height: 1.6;
}

:deep(.description-content p) {
  margin-top: 0;
  margin-bottom: 16px;
}

/* Styles pour le rapport statistique */
.stats-card {
  text-align: center;
  padding: 20px 0;
}

.main-stat {
  margin-bottom: 20px;
}

.main-stat .stat-value {
  font-size: 3em;
  font-weight: 800;
  color: var(--ion-color-primary);
  display: block;
}

.stats-grid {
  display: flex;
  justify-content: space-around;
  border-top: 1px solid var(--ion-color-light);
  padding-top: 20px;
}

.stat-item .stat-value {
  font-size: 1.5em;
  font-weight: bold;
  display: block;
}

.stat-label {
  font-size: 0.85em;
  color: var(--ion-color-medium);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

ion-note {
  font-size: 0.75em;
  line-height: 1.2;
}
</style>
