<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/admin/members"></ion-back-button>
        </ion-buttons>
        <ion-title v-if="member" v-html="member.title.rendered"></ion-title>
        <ion-title v-else>Détails Adhérent</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <ion-header collapse="condense">
          <ion-toolbar>
            <ion-title size="large">
              <div class="multiline-large-title" v-if="member" v-html="member.title.rendered"></div>
              <div class="multiline-large-title" v-else>Détails Adhérent</div>
            </ion-title>
          </ion-toolbar>
        </ion-header>

        <div v-if="member">
          <!-- Carte Identité -->
          <ion-card class="ion-no-margin ion-margin-bottom">
            <ion-card-header>
              <ion-card-title>Identité</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-list lines="none">
                <ion-item v-if="member.meta?._dame_birth_date">
                  <ion-label>
                    <p>Date de naissance</p>
                    <h3>{{ formatDate(member.meta._dame_birth_date) }}</h3>
                  </ion-label>
                </ion-item>
                <ion-item v-if="member.meta?._dame_sexe">
                  <ion-label>
                    <p>Sexe</p>
                    <h3>{{ formatGender(member.meta._dame_sexe) }}</h3>
                  </ion-label>
                </ion-item>
                <ion-item v-if="member.dame_age_category">
                  <ion-label>
                    <p>Catégorie</p>
                    <h3>{{ member.dame_age_category }}</h3>
                  </ion-label>
                </ion-item>
              </ion-list>
            </ion-card-content>
          </ion-card>

          <!-- Carte Licence -->
          <ion-card class="ion-no-margin ion-margin-bottom">
            <ion-card-header>
              <ion-card-title>Licence</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-list lines="none">
                <!-- Grille Infos Licence -->
                <div class="license-grid-container">
                  <div class="license-box" v-if="member.meta?._dame_license_number">
                    <span class="license-label">Licence FFE</span>
                    <span class="license-value">
                      {{ member.meta._dame_license_number }}
                      <small v-if="member.meta._dame_license_type">({{ member.meta._dame_license_type }})</small>
                    </span>
                  </div>
                  <div class="license-box" v-if="member.meta?._dame_fide_id">
                    <span class="license-label">FIDE ID</span>
                    <span class="license-value">{{ member.meta._dame_fide_id }}</span>
                  </div>
                </div>
                
                <!-- Grille Elo -->
                <div v-if="member.meta?._dame_elo_standard || member.meta?._dame_elo_rapide || member.meta?._dame_elo_blitz" class="elo-grid-container">
                  <div class="elo-box" v-if="member.meta?._dame_elo_standard">
                    <span class="elo-label">Standard</span>
                    <span class="elo-value">{{ member.meta._dame_elo_standard }}</span>
                  </div>
                  <div class="elo-box" v-if="member.meta?._dame_elo_rapide">
                    <span class="elo-label">Rapide</span>
                    <span class="elo-value">{{ member.meta._dame_elo_rapide }}</span>
                  </div>
                  <div class="elo-box" v-if="member.meta?._dame_elo_blitz">
                    <span class="elo-label">Blitz</span>
                    <span class="elo-value">{{ member.meta._dame_elo_blitz }}</span>
                  </div>
                </div>
              </ion-list>
            </ion-card-content>
          </ion-card>

          <!-- Carte Contact -->
          <ion-card class="ion-no-margin ion-margin-bottom">
            <ion-card-header>
              <ion-card-title>Contact</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-list lines="inset">
                <!-- Email -->
                <ion-item v-if="member.meta?._dame_email">
                  <ion-icon slot="start" :icon="mailOutline" color="primary"></ion-icon>
                  <ion-label>
                    <p>Email</p>
                    <h3>{{ member.meta._dame_email }}</h3>
                  </ion-label>
                  <ion-button 
                    slot="end" 
                    fill="clear" 
                    :href="'mailto:' + member.meta._dame_email"
                  >
                    <ion-icon slot="icon-only" :icon="sendOutline"></ion-icon>
                  </ion-button>
                </ion-item>
                
                <!-- Téléphone -->
                <ion-item v-if="member.meta?._dame_phone_number">
                  <ion-icon slot="start" :icon="callOutline" color="primary"></ion-icon>
                  <ion-label>
                    <p>Téléphone</p>
                    <h3>{{ member.meta._dame_phone_number }}</h3>
                  </ion-label>
                  <ion-button 
                    slot="end" 
                    fill="clear" 
                    :href="'tel:' + member.meta._dame_phone_number"
                  >
                    <ion-icon slot="icon-only" :icon="callOutline"></ion-icon>
                  </ion-button>
                </ion-item>

                <!-- Adresse -->
                <ion-item v-if="member.meta?._dame_address_1 || member.meta?._dame_city" lines="none">
                  <ion-icon slot="start" :icon="locationOutline" color="primary"></ion-icon>
                  <ion-label class="ion-text-wrap">
                    <p>Adresse postale</p>
                    <h3>{{ member.meta?._dame_address_1 }}</h3>
                    <h3 v-if="member.meta?._dame_address_2">{{ member.meta?._dame_address_2 }}</h3>
                    <h3>{{ member.meta?._dame_postal_code }} {{ member.meta?._dame_city }}</h3>
                  </ion-label>
                </ion-item>
              </ion-list>
            </ion-card-content>
          </ion-card>

          <!-- Carte Représentants Légaux -->
          <ion-card v-if="member.meta?._dame_legal_rep_1_last_name || member.meta?._dame_legal_rep_2_last_name" class="ion-no-margin ion-margin-bottom">
            <ion-card-header>
              <ion-card-title>Représentants Légaux</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-list lines="inset">
                <!-- Représentant 1 -->
                <div v-if="member.meta?._dame_legal_rep_1_last_name" class="ion-padding-bottom">
                  <ion-item-divider color="light">
                    <ion-label>
                      {{ member.meta._dame_legal_rep_1_first_name }} {{ member.meta._dame_legal_rep_1_last_name }}
                      <span v-if="member.meta._dame_legal_rep_1_profession">({{ member.meta._dame_legal_rep_1_profession }})</span>
                    </ion-label>
                  </ion-item-divider>
                  
                  <ion-item 
                    v-if="member.meta._dame_legal_rep_1_phone" 
                    :href="'tel:' + member.meta._dame_legal_rep_1_phone"
                    :detail="false"
                  >
                    <ion-icon slot="start" :icon="callOutline" color="primary"></ion-icon>
                    <ion-label>{{ member.meta._dame_legal_rep_1_phone }}</ion-label>
                    <ion-icon slot="end" :icon="callOutline" color="primary" size="small"></ion-icon>
                  </ion-item>
                  
                  <ion-item 
                    v-if="member.meta._dame_legal_rep_1_email" 
                    :href="'mailto:' + member.meta._dame_legal_rep_1_email"
                    :detail="false"
                  >
                    <ion-icon slot="start" :icon="mailOutline" color="primary"></ion-icon>
                    <ion-label>{{ member.meta._dame_legal_rep_1_email }}</ion-label>
                    <ion-icon slot="end" :icon="sendOutline" color="primary" size="small"></ion-icon>
                  </ion-item>
                </div>

                <!-- Représentant 2 -->
                <div v-if="member.meta?._dame_legal_rep_2_last_name">
                  <ion-item-divider color="light">
                    <ion-label>
                      {{ member.meta._dame_legal_rep_2_first_name }} {{ member.meta._dame_legal_rep_2_last_name }}
                      <span v-if="member.meta._dame_legal_rep_2_profession">({{ member.meta._dame_legal_rep_2_profession }})</span>
                    </ion-label>
                  </ion-item-divider>
                  
                  <ion-item 
                    v-if="member.meta._dame_legal_rep_2_phone" 
                    :href="'tel:' + member.meta._dame_legal_rep_2_phone"
                    :detail="false"
                  >
                    <ion-icon slot="start" :icon="callOutline" color="primary"></ion-icon>
                    <ion-label>{{ member.meta._dame_legal_rep_2_phone }}</ion-label>
                    <ion-icon slot="end" :icon="callOutline" color="primary" size="small"></ion-icon>
                  </ion-item>
                  
                  <ion-item 
                    v-if="member.meta._dame_legal_rep_2_email" 
                    :href="'mailto:' + member.meta._dame_legal_rep_2_email"
                    :detail="false"
                  >
                    <ion-icon slot="start" :icon="mailOutline" color="primary"></ion-icon>
                    <ion-label>{{ member.meta._dame_legal_rep_2_email }}</ion-label>
                    <ion-icon slot="end" :icon="sendOutline" color="primary" size="small"></ion-icon>
                  </ion-item>
                </div>
              </ion-list>
            </ion-card-content>
          </ion-card>
        </div>

        <!-- Chargement ou Introuvable -->
        <div v-else class="ion-text-center ion-padding mt-large">
          <div v-if="memberStore.isLoading">
            <ion-spinner name="crescent"></ion-spinner>
            <p>Chargement des données...</p>
          </div>
          <div v-else>
            <ion-icon :icon="personOutline" size="large" color="medium"></ion-icon>
            <h2>Adhérent introuvable</h2>
            <p>Cet adhérent n'existe pas ou la liste n'est pas encore chargée.</p>
            <ion-button expand="block" fill="outline" router-link="/tabs/members" class="ion-margin-top">
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
  IonItemDivider
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
import { useMemberStore } from '@/stores/members';

const route = useRoute();
const memberStore = useMemberStore();

const memberId = parseInt(route.params.id as string);

/**
 * Récupère l'adhérent correspondant dans le store
 */
const member = computed(() => {
  return memberStore.members.find(m => m.id === memberId);
});

/**
 * Formate la date de naissance (YYYY-MM-DD -> DD/MM/YYYY)
 */
const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-';
  return dateStr.split('-').reverse().join('/');
};

/**
 * Formate le genre pour l'affichage
 */
const formatGender = (gender?: string) => {
  if (!gender) return '-';
  const mapping: Record<string, string> = {
    'M': 'Masculin',
    'F': 'Féminin',
    'H': 'Masculin'
  };
  return mapping[gender] || gender;
};
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

ion-item-divider {
  margin-top: 10px;
  --padding-start: 10px;
}

ion-item-divider ion-label {
  font-weight: bold;
  color: var(--ion-color-dark);
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

.elo-grid-container {
  display: flex;
  gap: 10px;
  padding: 10px 16px;
  justify-content: space-between;
}

.license-grid-container {
  display: flex;
  gap: 10px;
  padding: 0 16px 10px 16px;
}

.license-box,
.elo-box {
  flex: 1;
  background: var(--ion-color-light);
  border-radius: 8px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-width: 0;
}

.license-label,
.elo-label {
  font-size: 0.75em;
  color: var(--ion-color-medium);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 2px;
}

.license-value,
.elo-value {
  font-weight: bold;
  font-size: 1.1em;
  color: var(--ion-color-dark);
}

.license-value small {
  font-weight: normal;
  font-size: 0.8em;
  margin-left: 4px;
}
</style>
