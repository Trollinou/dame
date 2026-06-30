<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/home"></ion-back-button>
        </ion-buttons>
        <ion-title>Préinscription {{ authStore.currentSeason ? authStore.currentSeason : 'Saison' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <div class="form-container">
          
          <!-- Écran de Succès -->
          <div v-if="successData" class="success-card ion-padding ion-text-center animate-fade-in">
            <ion-icon :icon="checkmarkCircleOutline" color="success" class="success-icon"></ion-icon>
            <h2>Préinscription Enregistrée !</h2>
            <p class="ion-margin-bottom">{{ successData.message }}</p>

            <ion-card class="pdf-card ion-no-margin ion-margin-bottom">
              <ion-card-header>
                <ion-card-title style="font-size: 1.1em;">Documents à télécharger</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div class="pdf-buttons">
                  <ion-button expand="block" color="secondary" @click="downloadPdf('health')">
                    <ion-icon slot="start" :icon="documentTextOutline"></ion-icon>
                    Attestation de Santé
                  </ion-button>
                  <ion-button v-if="successData.is_minor" expand="block" color="secondary" @click="downloadPdf('parental')">
                    <ion-icon slot="start" :icon="documentTextOutline"></ion-icon>
                    Autorisation Parentale
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>

            <div v-if="successData.payment_url" class="ion-margin-bottom">
              <p>Pour finaliser l'inscription, vous pouvez procéder au paiement en ligne :</p>
              <ion-button :href="successData.payment_url" target="_blank" color="primary" expand="block">
                Payer mon adhésion
              </ion-button>
            </div>

            <ion-button fill="outline" color="medium" @click="resetForm" expand="block">
              Faire une nouvelle préinscription
            </ion-button>
          </div>

          <!-- Formulaire -->
          <div v-else-if="authStore.isAuthenticated && hasLoadedIdentities && registrationTargets.length === 0" class="ion-text-center ion-padding" style="margin-top: 40px;">
            <ion-icon :icon="checkmarkCircleOutline" color="success" style="font-size: 5rem;"></ion-icon>
            <h2 style="font-weight: 600; margin-top: 15px;">Foyer à jour !</h2>
            <p style="color: var(--ion-color-step-600); line-height: 1.5; margin: 15px 0;">
              Tous les membres associés à votre adresse e-mail sont déjà inscrits pour la saison en cours. Aucune réinscription n'est nécessaire.
            </p>
          </div>

          <form v-else @submit.prevent="submitForm">
            <!-- Choix adhérent si connecté (Adhérent et/ou Resp. Légal) -->
            <ion-card v-if="authStore.isAuthenticated && registrationTargets.length > 0" class="ion-no-margin ion-margin-bottom identity-select-card">
              <ion-card-content>
                <ion-item lines="none">
                  <ion-select
                    label="Pour qui souhaitez-vous faire la préinscription ?"
                    label-placement="stacked"
                    placeholder="Sélectionner..."
                    @ionChange="handlePrefillSelection($event)"
                    style="width: 100%;"
                    :value="selectedTargetId"
                  >
                    <ion-select-option :value="0">-- Nouvelle préinscription (vierge) --</ion-select-option>
                    <ion-select-option 
                      v-for="opt in registrationTargets" 
                      :key="opt.member_id" 
                      :value="opt.member_id"
                    >
                      <template v-if="completedMemberIds.includes(opt.member_id)">
                        ✅ {{ opt.name }} ({{ opt.relation }} - Rempli)
                      </template>
                      <template v-else>
                        {{ opt.name }} ({{ opt.relation }})
                      </template>
                    </ion-select-option>
                  </ion-select>
                </ion-item>
              </ion-card-content>
            </ion-card>

            <!-- SECTION 1 : Informations Adhérent -->
            <div class="form-section">
              <h3 class="section-title">Informations de l'adhérent</h3>
              
              <ion-list lines="full" class="ion-no-padding">
                <ion-item>
                  <ion-input
                    v-model="form.dame_birth_name"
                    label="Nom de naissance *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_last_name"
                    label="Nom d'usage"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_first_name"
                    label="Prénom *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <div class="radio-group-container">
                    <span class="input-label">Sexe *</span>
                    <div class="radio-options">
                      <label><input type="radio" v-model="form.dame_sexe" value="Masculin"> Masculin</label>
                      <label><input type="radio" v-model="form.dame_sexe" value="Féminin"> Féminin</label>
                      <label><input type="radio" v-model="form.dame_sexe" value="Non précisé"> Non précisé</label>
                    </div>
                  </div>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_birth_date"
                    type="date"
                    label="Date de naissance *"
                    label-placement="stacked"
                    required
                    @ionChange="checkAge"
                  ></ion-input>
                </ion-item>

                <!-- Ville de naissance avec autocomplétion -->
                <ion-item>
                  <ion-input
                    v-model="form.dame_birth_city"
                    label="Lieu de naissance"
                    label-placement="stacked"
                    @ionInput="e => searchBirthCity(e.detail.value || '')"
                    :placeholder="isMinor ? '' : 'Obligatoire pour les majeurs'"
                  ></ion-input>
                </ion-item>
                <div v-if="suggestions.birthCity.length > 0" class="suggestions-outer-container">
                  <ul class="suggestions-list">
                    <li 
                      v-for="s in suggestions.birthCity" 
                      :key="s" 
                      @click="selectBirthCity(s)"
                    >
                      {{ s }}
                    </li>
                  </ul>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_phone_number"
                    type="tel"
                    label="Numéro de téléphone *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_email"
                    type="email"
                    label="Email *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item lines="none" class="optin-item" style="--background: transparent; margin-top: -5px; margin-bottom: 5px;">
                  <ion-checkbox
                    v-model="form.dame_refuses_comms"
                    style="--size: 18px; font-size: 0.85em; --border-radius: 4px;"
                  >
                    <span style="white-space: normal; line-height: 1.3; display: block; color: var(--ion-color-medium); font-size: 0.9em; margin-left: 8px;">
                      Je m'oppose à la réception des e-mails d'information de l'association. (Nous utilisons un indicateur de lecture afin de nous assurer que nos messages importants vous parviennent bien).
                    </span>
                  </ion-checkbox>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_profession"
                    label="Profession"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <!-- Adresse avec autocomplétion -->
                <ion-item>
                  <ion-input
                    v-model="form.dame_address_1"
                    label="Adresse *"
                    label-placement="stacked"
                    required
                    @ionInput="e => searchAddress(e.detail.value || '')"
                  ></ion-input>
                </ion-item>
                <div v-if="suggestions.address.length > 0" class="suggestions-outer-container">
                  <ul class="suggestions-list">
                    <li 
                      v-for="s in suggestions.address" 
                      :key="s.fulltext" 
                      @click="selectAddress(s)"
                    >
                      {{ s.fulltext }}
                    </li>
                  </ul>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_address_2"
                    label="Complément d'adresse"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_postal_code"
                    label="Code Postal"
                    label-placement="stacked"
                    style="max-width: 120px;"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_city"
                    label="Ville *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-select
                    v-model="form.dame_taille_vetements"
                    label="Taille de vêtements"
                    label-placement="stacked"
                  >
                    <ion-select-option v-for="size in clothingSizes" :key="size" :value="size">
                      {{ size }}
                    </ion-select-option>
                  </ion-select>
                </ion-item>

                <ion-item>
                  <ion-select
                    v-model="form.dame_license_type"
                    label="Type de licence *"
                    label-placement="stacked"
                    required
                  >
                    <ion-select-option value="A">Licence A (Cours + Compétition)</ion-select-option>
                    <ion-select-option value="B">Licence B (Jeu libre)</ion-select-option>
                  </ion-select>
                </ion-item>
              </ion-list>
            </div>

            <!-- SECTION 2 : Représentants légaux (Uniquement si mineur) -->
            <div v-if="isMinor" class="form-section animate-fade-in">
              <h3 class="section-title">Représentant Légal 1</h3>
              <div class="copy-adh-container ion-padding-bottom">
                <ion-button size="small" fill="outline" color="secondary" @click="copyAdherentData(1)">
                  Copier les données de l'adhérent
                </ion-button>
              </div>

              <ion-list lines="full" class="ion-no-padding">
                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_last_name"
                    label="Nom de naissance *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_first_name"
                    label="Prénom *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <div class="legal-rep-prevention-note ion-padding-horizontal">
                  <small>Dans le cadre de notre politique de prévention des violences, merci de renseigner les champs ci-dessous.</small>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_date_naissance"
                    type="date"
                    label="Date de naissance"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_commune_naissance"
                    label="Lieu de naissance"
                    label-placement="stacked"
                    @ionInput="e => searchBirthCity(e.detail.value || '', 1)"
                  ></ion-input>
                </ion-item>
                <div v-if="suggestions.rep1BirthCity.length > 0" class="suggestions-outer-container">
                  <ul class="suggestions-list">
                    <li 
                      v-for="s in suggestions.rep1BirthCity" 
                      :key="s" 
                      @click="selectBirthCity(s, 1)"
                    >
                      {{ s }}
                    </li>
                  </ul>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_phone"
                    type="tel"
                    label="Numéro de téléphone *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_email"
                    type="email"
                    label="Email *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_profession"
                    label="Profession"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <!-- Adresse RL1 -->
                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_address_1"
                    label="Adresse *"
                    label-placement="stacked"
                    required
                    @ionInput="e => searchAddress(e.detail.value || '', 1)"
                  ></ion-input>
                </ion-item>
                <div v-if="suggestions.rep1Address.length > 0" class="suggestions-outer-container">
                  <ul class="suggestions-list">
                    <li 
                      v-for="s in suggestions.rep1Address" 
                      :key="s.fulltext" 
                      @click="selectAddress(s, 1)"
                    >
                      {{ s.fulltext }}
                    </li>
                  </ul>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_address_2"
                    label="Complément d'adresse"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_postal_code"
                    label="Code Postal *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_1_city"
                    label="Ville *"
                    label-placement="stacked"
                    required
                  ></ion-input>
                </ion-item>
              </ion-list>

              <!-- Représentant Légal 2 -->
              <h3 class="section-title ion-margin-top">Représentant Légal 2 (Optionnel)</h3>
              <div class="copy-adh-container ion-padding-bottom">
                <ion-button size="small" fill="outline" color="secondary" @click="copyAdherentData(2)">
                  Copier les données de l'adhérent
                </ion-button>
              </div>

              <ion-list lines="full" class="ion-no-padding">
                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_last_name"
                    label="Nom de naissance"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_first_name"
                    label="Prénom"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_date_naissance"
                    type="date"
                    label="Date de naissance"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_commune_naissance"
                    label="Lieu de naissance"
                    label-placement="stacked"
                    @ionInput="e => searchBirthCity(e.detail.value || '', 2)"
                  ></ion-input>
                </ion-item>
                <div v-if="suggestions.rep2BirthCity.length > 0" class="suggestions-outer-container">
                  <ul class="suggestions-list">
                    <li 
                      v-for="s in suggestions.rep2BirthCity" 
                      :key="s" 
                      @click="selectBirthCity(s, 2)"
                    >
                      {{ s }}
                    </li>
                  </ul>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_phone"
                    type="tel"
                    label="Numéro de téléphone"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_email"
                    type="email"
                    label="Email"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_profession"
                    label="Profession"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_address_1"
                    label="Adresse"
                    label-placement="stacked"
                    @ionInput="e => searchAddress(e.detail.value || '', 2)"
                  ></ion-input>
                </ion-item>
                <div v-if="suggestions.rep2Address.length > 0" class="suggestions-outer-container">
                  <ul class="suggestions-list">
                    <li 
                      v-for="s in suggestions.rep2Address" 
                      :key="s.fulltext" 
                      @click="selectAddress(s, 2)"
                    >
                      {{ s.fulltext }}
                    </li>
                  </ul>
                </div>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_address_2"
                    label="Complément d'adresse"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_postal_code"
                    label="Code Postal"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>

                <ion-item>
                  <ion-input
                    v-model="form.dame_legal_rep_2_city"
                    label="Ville"
                    label-placement="stacked"
                  ></ion-input>
                </ion-item>
              </ion-list>
            </div>

            <!-- SECTION 3 : Santé & Consentement -->
            <div class="form-section ion-margin-top">
              <h3 class="section-title">Questionnaire de santé</h3>
              
              <div class="health-info-box ion-padding-bottom">
                <p>
                  Veuillez répondre au questionnaire officiel. Si vous répondez "OUI" à au moins une question, vous devrez fournir un certificat médical.
                </p>
                <ion-button fill="clear" color="primary" size="small" :href="`${siteUrl}/wp-content/plugins/dame/assets/pdf/questionnaire_sante_majeur.pdf`" target="_blank">
                  🔗 Ouvrir le Questionnaire Majeurs
                </ion-button>
                <ion-button fill="clear" color="primary" size="small" :href="`${siteUrl}/wp-content/plugins/dame/assets/pdf/questionnaire_sante_mineur.pdf`" target="_blank">
                  🔗 Ouvrir le Questionnaire Mineurs
                </ion-button>
              </div>

              <ion-list lines="none">
                <ion-item>
                  <div class="radio-group-container">
                    <span class="input-label">Vos réponses au questionnaire *</span>
                    <div class="radio-options vertical">
                      <label><input type="radio" v-model="form.dame_health_questionnaire" value="non"> J'ai répondu NON partout</label>
                      <label><input type="radio" v-model="form.dame_health_questionnaire" value="oui"> J'ai au moins une réponse à OUI</label>
                    </div>
                  </div>
                </ion-item>

                <ion-item style="margin-top: 15px;">
                  <ion-checkbox v-model="consentCheckbox" required justify="start" label-placement="end" style="--size: 20px;">
                    <span class="consent-text">
                      En cochant cette case, je reconnais avoir pris connaissance du règlement intérieur de l’Association Échiquier Lédonien et m’engage à le respecter. *
                    </span>
                  </ion-checkbox>
                </ion-item>
              </ion-list>
            </div>

            <div class="error-banner ion-margin-top ion-padding-horizontal" v-if="errorMessage">
              <ion-text color="danger">
                <p v-html="errorMessage"></p>
              </ion-text>
            </div>

            <div class="submit-container ion-margin-top">
              <ion-button 
                type="submit" 
                color="primary" 
                expand="block" 
                :disabled="isSubmitting || !consentCheckbox || !form.dame_health_questionnaire"
              >
                <ion-spinner v-if="isSubmitting" name="crescent"></ion-spinner>
                <span v-else>Valider ma préinscription</span>
              </ion-button>
              
              <p class="privacy-disclaimer" style="font-size: 0.82em; color: var(--ion-color-medium); margin-top: 12px; line-height: 1.4; text-align: center;">
                Les données collectées sur ce formulaire sont nécessaires à la gestion de votre adhésion. Pour en savoir plus sur l'utilisation de vos données, de nos outils de communication et pour exercer vos droits, consultez nos Mentions Légales.
              </p>
            </div>
          </form>

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
  IonList,
  IonItem,
  IonInput,
  IonSelect,
  IonSelectOption,
  IonCheckbox,
  IonButton,
  IonIcon,
  IonSpinner,
  IonText,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  onIonViewWillEnter
} from '@ionic/vue';
import { 
  checkmarkCircleOutline, 
  documentTextOutline 
} from 'ionicons/icons';
import { ref, reactive, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute } from 'vue-router';

const authStore = useAuthStore();
const route = useRoute();
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL;
const siteUrl = apiBaseUrl.replace(/\/wp-json\/?$/, '');

// Options de taille
const clothingSizes = ['Non renseigné', '8/10', '10/12', '12/14', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

// Données formulaire réactives
const form = reactive({
  dame_birth_name: '',
  dame_last_name: '',
  dame_first_name: '',
  dame_sexe: 'Masculin',
  dame_birth_date: '',
  dame_birth_city: '',
  dame_phone_number: '',
  dame_email: '',
  dame_profession: '',
  dame_address_1: '',
  dame_address_2: '',
  dame_postal_code: '',
  dame_city: '',
  dame_taille_vetements: 'Non renseigné',
  dame_license_type: 'A',
  dame_legal_rep_1_first_name: '',
  dame_legal_rep_1_last_name: '',
  dame_legal_rep_1_email: '',
  dame_legal_rep_1_phone: '',
  dame_legal_rep_1_address_1: '',
  dame_legal_rep_1_address_2: '',
  dame_legal_rep_1_postal_code: '',
  dame_legal_rep_1_city: '',
  dame_legal_rep_1_profession: '',
  dame_legal_rep_1_date_naissance: '',
  dame_legal_rep_1_commune_naissance: '',
  dame_legal_rep_2_first_name: '',
  dame_legal_rep_2_last_name: '',
  dame_legal_rep_2_email: '',
  dame_legal_rep_2_phone: '',
  dame_legal_rep_2_address_1: '',
  dame_legal_rep_2_address_2: '',
  dame_legal_rep_2_postal_code: '',
  dame_legal_rep_2_city: '',
  dame_legal_rep_2_profession: '',
  dame_legal_rep_2_date_naissance: '',
  dame_legal_rep_2_commune_naissance: '',
  dame_health_questionnaire: '',
  dame_refuses_comms: false,
});

const consentCheckbox = ref(false);
const isMinor = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');
const successData = ref<any>(null);

// Autocomplétion
const suggestions = reactive({
  birthCity: [] as string[],
  rep1BirthCity: [] as string[],
  rep2BirthCity: [] as string[],
  address: [] as any[],
  rep1Address: [] as any[],
  rep2Address: [] as any[]
});

let searchTimeout: any = null;

// Cibles de préinscription
const registrationTargets = ref<any[]>([]);
const selectedTargetId = ref<number>(0);
const completedMemberIds = ref<number[]>([]);
const hasLoadedIdentities = ref(false);

onIonViewWillEnter(async () => {
  resetForm();
  
  if (authStore.isAuthenticated) {
    try {
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/my-identities`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      });
      if (response.ok) {
        const identities = await response.json();
        const targets: any[] = [];
        
        // 1. Chercher si l'utilisateur lui-même a des fiches adhérents directes
        identities.forEach((identity: any) => {
          if (identity.type === 'member' && identity.member_id > 0 && !identity.already_registered) {
            targets.push({
              member_id: identity.member_id,
              name: identity.name,
              relation: 'Moi-même'
            });
          }
          // 2. Chercher les enfants / membres associés du parent
          if (identity.type === 'representative' && identity.associated_members) {
            identity.associated_members.forEach((child: any) => {
              if (!child.already_registered && !targets.some(t => t.member_id === child.member_id)) {
                targets.push({
                  member_id: child.member_id,
                  name: child.firstname || child.name,
                  relation: 'Enfant/Associé'
                });
              }
            });
          }
        });

        registrationTargets.value = targets;
        hasLoadedIdentities.value = true;

        // Pré-remplir automatiquement s'il y a exactement 1 cible
        if (targets.length === 1) {
          selectedTargetId.value = targets[0].member_id;
          prefillAdherent(targets[0].member_id);
        } else if (targets.length > 1 && authStore.selectedIdentity && authStore.selectedIdentity.member_id > 0 && !authStore.selectedIdentity.already_registered) {
          // Si l'identité sélectionnée est un adhérent et n'est pas déjà inscrite
          selectedTargetId.value = authStore.selectedIdentity.member_id;
          prefillAdherent(authStore.selectedIdentity.member_id);
        }
      }
    } catch (err) {
      console.error("Erreur lors du chargement des identités:", err);
    }
  }
});


// Réinitialisation du formulaire
const resetForm = () => {
  form.dame_birth_name = '';
  form.dame_last_name = '';
  form.dame_first_name = '';
  form.dame_sexe = 'Masculin';
  form.dame_birth_date = '';
  form.dame_birth_city = '';
  form.dame_phone_number = '';
  form.dame_email = '';
  form.dame_profession = '';
  form.dame_address_1 = '';
  form.dame_address_2 = '';
  form.dame_postal_code = '';
  form.dame_city = '';
  form.dame_taille_vetements = 'Non renseigné';
  form.dame_license_type = 'A';
  form.dame_legal_rep_1_first_name = '';
  form.dame_legal_rep_1_last_name = '';
  form.dame_legal_rep_1_email = '';
  form.dame_legal_rep_1_phone = '';
  form.dame_legal_rep_1_address_1 = '';
  form.dame_legal_rep_1_address_2 = '';
  form.dame_legal_rep_1_postal_code = '';
  form.dame_legal_rep_1_city = '';
  form.dame_legal_rep_1_profession = '';
  form.dame_legal_rep_1_date_naissance = '';
  form.dame_legal_rep_1_commune_naissance = '';
  form.dame_legal_rep_2_first_name = '';
  form.dame_legal_rep_2_last_name = '';
  form.dame_legal_rep_2_email = '';
  form.dame_legal_rep_2_phone = '';
  form.dame_legal_rep_2_address_1 = '';
  form.dame_legal_rep_2_address_2 = '';
  form.dame_legal_rep_2_postal_code = '';
  form.dame_legal_rep_2_city = '';
  form.dame_legal_rep_2_profession = '';
  form.dame_legal_rep_2_date_naissance = '';
  form.dame_legal_rep_2_commune_naissance = '';
  form.dame_health_questionnaire = '';
  form.dame_refuses_comms = false;
  consentCheckbox.value = false;
  isMinor.value = false;
  isSubmitting.value = false;
  errorMessage.value = '';
  successData.value = null;
  selectedTargetId.value = 0;
};

// Vérification de la minorité de l'adhérent
const checkAge = () => {
  if (!form.dame_birth_date) {
    isMinor.value = false;
    return;
  }
  const birth = new Date(form.dame_birth_date);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
    age--;
  }
  isMinor.value = age < 18;
};

// Copier les données de l'adhérent pour le représentant légal
const copyAdherentData = (repNum: number) => {
  if (repNum === 1) {
    form.dame_legal_rep_1_last_name = form.dame_last_name || form.dame_birth_name;
    form.dame_legal_rep_1_first_name = form.dame_first_name;
    form.dame_legal_rep_1_phone = form.dame_phone_number;
    form.dame_legal_rep_1_email = form.dame_email;
    form.dame_legal_rep_1_address_1 = form.dame_address_1;
    form.dame_legal_rep_1_address_2 = form.dame_address_2;
    form.dame_legal_rep_1_postal_code = form.dame_postal_code;
    form.dame_legal_rep_1_city = form.dame_city;
  } else {
    form.dame_legal_rep_2_last_name = form.dame_last_name || form.dame_birth_name;
    form.dame_legal_rep_2_first_name = form.dame_first_name;
    form.dame_legal_rep_2_phone = form.dame_phone_number;
    form.dame_legal_rep_2_email = form.dame_email;
    form.dame_legal_rep_2_address_1 = form.dame_address_1;
    form.dame_legal_rep_2_address_2 = form.dame_address_2;
    form.dame_legal_rep_2_postal_code = form.dame_postal_code;
    form.dame_legal_rep_2_city = form.dame_city;
  }
};

// Gérer la sélection de pré-remplissage par un responsable légal
const handlePrefillSelection = (event: any) => {
  const memberId = event.detail.value;
  selectedTargetId.value = memberId;
  if (memberId) {
    prefillAdherent(memberId);
  } else {
    resetForm();
  }
};

// Récupérer et pré-remplir les données d'un adhérent
const prefillAdherent = async (memberId: number) => {
  errorMessage.value = '';
  
  const makeRequest = async () => {
    return fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/adherent-details?adherent_id=${memberId}`, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    });
  };

  try {
    let response = await makeRequest();
    
    // Si expiré / non autorisé, on tente de rafraîchir la session une fois
    if (response.status === 401) {
      console.warn("Token expiré ou invalide (401), tentative de rafraîchissement...");
      await authStore.validateSession();
      // On re-tente l'appel
      response = await makeRequest();
    }

    if (response.ok) {
      const data = await response.json();
      
      // Mapper les données dans notre objet réactif
      Object.keys(data).forEach((key) => {
        const formKey = `dame_${key}`;
        if (formKey in form && data[key] !== null && data[key] !== undefined) {
          (form as any)[formKey] = data[key];
        }
      });

      checkAge();
    } else if (response.status === 401) {
      console.error("Session définitivement expirée.");
      authStore.logout();
    }
  } catch (err) {
    console.error("Erreur lors de la récupération des détails adhérent:", err);
  }
};


// Soumettre le formulaire
const submitForm = async () => {
  errorMessage.value = '';
  isSubmitting.value = true;

  try {
    const headers: Record<string, string> = { 'Content-Type': 'application/json' };
    if (authStore.isAuthenticated) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
    }

    const bodyData = {
      ...form,
      dame_consent_checkbox: consentCheckbox.value ? '1' : ''
    };

    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/dame/v1/pre-inscription`, {
      method: 'POST',
      headers,
      body: JSON.stringify(bodyData)
    });

    const resData = await response.json();

    if (!response.ok) {
      errorMessage.value = resData.message || "Une erreur est survenue lors de la validation.";
      return;
    }

    successData.value = resData;

    // Ajouter le membre actuel aux membres déjà inscrits durant cette session
    if (selectedTargetId.value > 0) {
      completedMemberIds.value.push(selectedTargetId.value);
    }
  } catch (err) {
    console.error(err);
    errorMessage.value = "Erreur de connexion au serveur.";
  } finally {
    isSubmitting.value = false;
  }
};

// Téléchargement sécurisé du PDF via fetch
const downloadPdf = async (type: 'health' | 'parental') => {
  if (!successData.value) return;
  const post_id = successData.value.post_id;
  const token = successData.value.download_token;

  try {
    const url = `${import.meta.env.VITE_API_BASE_URL}/dame/v1/pre-inscriptions/${post_id}/pdf/${type}?token=${token}`;
    const headers: Record<string, string> = {};
    if (authStore.isAuthenticated) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
    }

    const response = await fetch(url, { headers });
    if (!response.ok) throw new Error("Erreur de téléchargement");

    const blob = await response.blob();
    const blobUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = blobUrl;
    
    // Ajout du nom et prénom de l'adhérent dans le nom de fichier
    const lastName = (form.dame_last_name || '').trim().replace(/\s+/g, '_').toUpperCase();
    const firstName = (form.dame_first_name || '').trim().replace(/\s+/g, '_');
    const nameSuffix = (lastName && firstName) ? `_${lastName}_${firstName}` : '';
    const baseName = type === 'health' ? 'attestation_sante' : 'autorisation_parentale';
    
    link.download = `${baseName}${nameSuffix}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);
  } catch (err) {
    console.error(err);
    alert("Impossible de générer et télécharger le document PDF.");
  }
};

// Recherche de commune de naissance (geo.api.gouv.fr)
const searchBirthCity = (query: string, repNum: number = 0) => {
  clearTimeout(searchTimeout);
  if (query.length < 3) {
    clearSuggestions(repNum);
    return;
  }

  searchTimeout = setTimeout(() => {
    fetch(`https://geo.api.gouv.fr/communes?fields=nom,codesPostaux&nom=${encodeURIComponent(query)}`)
      .then(r => r.json())
      .then(data => {
        const list = data.slice(0, 5).map((c: any) => `${c.nom} (${c.codesPostaux[0] || ''})`);
        if (repNum === 0) suggestions.birthCity = list;
        else if (repNum === 1) suggestions.rep1BirthCity = list;
        else if (repNum === 2) suggestions.rep2BirthCity = list;
      })
      .catch(() => {});
  }, 250);
};

const selectBirthCity = (city: string, repNum: number = 0) => {
  if (repNum === 0) {
    form.dame_birth_city = city;
    suggestions.birthCity = [];
  } else if (repNum === 1) {
    form.dame_legal_rep_1_commune_naissance = city;
    suggestions.rep1BirthCity = [];
  } else if (repNum === 2) {
    form.dame_legal_rep_2_commune_naissance = city;
    suggestions.rep2BirthCity = [];
  }
};

// Recherche d'adresse (data.geopf.fr/geocodage)
const searchAddress = (query: string, repNum: number = 0) => {
  clearTimeout(searchTimeout);
  if (query.length < 5) {
    if (repNum === 0) suggestions.address = [];
    else if (repNum === 1) suggestions.rep1Address = [];
    else if (repNum === 2) suggestions.rep2Address = [];
    return;
  }

  searchTimeout = setTimeout(() => {
    fetch(`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(query)}&type=StreetAddress`)
      .then(r => r.json())
      .then(data => {
        const list = data.results || [];
        if (repNum === 0) suggestions.address = list;
        else if (repNum === 1) suggestions.rep1Address = list;
        else if (repNum === 2) suggestions.rep2Address = list;
      })
      .catch(() => {});
  }, 250);
};

const selectAddress = (feature: any, repNum: number = 0) => {
  const text = feature.fulltext.split(',')[0].trim();
  if (repNum === 0) {
    form.dame_address_1 = text;
    form.dame_postal_code = feature.zipcode;
    form.dame_city = feature.city;
    suggestions.address = [];
  } else if (repNum === 1) {
    form.dame_legal_rep_1_address_1 = text;
    form.dame_legal_rep_1_postal_code = feature.zipcode;
    form.dame_legal_rep_1_city = feature.city;
    suggestions.rep1Address = [];
  } else if (repNum === 2) {
    form.dame_legal_rep_2_address_1 = text;
    form.dame_legal_rep_2_postal_code = feature.zipcode;
    form.dame_legal_rep_2_city = feature.city;
    suggestions.rep2Address = [];
  }
};

const clearSuggestions = (repNum: number) => {
  if (repNum === 0) suggestions.birthCity = [];
  else if (repNum === 1) suggestions.rep1BirthCity = [];
  else if (repNum === 2) suggestions.rep2BirthCity = [];
};
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}

.form-container {
  max-width: 600px;
  margin: 0 auto;
  padding-bottom: 40px;
}

.section-title {
  font-size: 1.25em;
  font-weight: 700;
  color: var(--ion-color-primary);
  margin-top: 25px;
  margin-bottom: 12px;
  border-bottom: 2px solid var(--ion-color-step-100, #e0e0e0);
  padding-bottom: 6px;
}

.copy-adh-container {
  display: flex;
  justify-content: flex-end;
}

.legal-rep-prevention-note {
  background: var(--ion-color-step-50, #f9f9f9);
  padding: 8px 16px;
  font-style: italic;
  color: var(--ion-color-medium);
}

.health-info-box {
  background: var(--ion-color-step-50, #f4f5f8);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 15px;
  font-size: 0.9em;
  color: var(--ion-color-step-800, #444);
}

.radio-group-container {
  padding: 10px 0;
  width: 100%;
}

.input-label {
  font-size: 0.9em;
  color: var(--ion-color-medium);
  display: block;
  margin-bottom: 8px;
}

.radio-options {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.radio-options.vertical {
  flex-direction: column;
  gap: 10px;
}

.radio-options label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.95em;
  cursor: pointer;
}

.consent-text {
  font-size: 0.85em;
  line-height: 1.4;
  color: var(--ion-color-dark);
}

.autocomplete-item {
  position: relative;
}

.autocomplete-wrapper {
  position: relative;
}

.suggestions-outer-container {
  background: var(--ion-color-step-0, #fff);
  border-left: 1px solid var(--ion-color-step-150, #ccc);
  border-right: 1px solid var(--ion-color-step-150, #ccc);
  border-bottom: 1px solid var(--ion-color-step-150, #ccc);
  margin-top: -1px;
  z-index: 100;
  position: relative;
}

.suggestions-list {
  margin: 0;
  padding: 0;
  list-style: none;
  max-height: 200px;
  overflow-y: auto;
}

.suggestions-list li {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid var(--ion-color-step-50, #eee);
  font-size: 0.95em;
  color: var(--ion-color-dark);
}

.suggestions-list li:hover {
  background: var(--ion-color-step-50, #f0f0f0);
}


.success-card {
  background: var(--ion-color-step-0, #fff);
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.success-icon {
  font-size: 4rem;
  margin-bottom: 15px;
}

.pdf-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.error-banner {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
  border-radius: 8px;
  padding: 12px 16px;
  font-weight: 500;
}

.identity-select-card {
  --background: var(--ion-color-primary-contrast, #f4f5f8);
  border-left: 4px solid var(--ion-color-primary);
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
