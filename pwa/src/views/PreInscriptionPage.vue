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
          <PreInscriptionSuccessCard
            v-if="successData"
            :success-data="successData"
            @download-pdf="onDownloadPdf"
            @reset="onResetForm"
          />

          <!-- Écran Foyer à Jour -->
          <div
            v-else-if="authStore.isAuthenticated && hasLoadedIdentities && registrationTargets.length === 0"
            class="ion-text-center ion-padding"
            style="margin-top: 40px;"
          >
            <ion-icon :icon="checkmarkCircleOutline" color="success" style="font-size: 5rem;"></ion-icon>
            <h2 style="font-weight: 600; margin-top: 15px;">Foyer à jour !</h2>
            <p style="color: var(--ion-color-step-600); line-height: 1.5; margin: 15px 0;">
              Tous les membres associés à votre adresse e-mail sont déjà inscrits pour la saison en cours. Aucune réinscription n'est nécessaire.
            </p>
          </div>

          <!-- Formulaire -->
          <form v-else @submit.prevent="onSubmit">
            <!-- Choix adhérent si connecté -->
            <PreInscriptionIdentitySelector
              v-if="authStore.isAuthenticated && registrationTargets.length > 0"
              :registration-targets="registrationTargets"
              :selected-target-id="selectedTargetId"
              :completed-member-ids="completedMemberIds"
              @select-target="handlePrefillSelection"
            />

            <!-- SECTION 1 : Informations Adhérent -->
            <PreInscriptionMemberSection
              :clothing-sizes="clothingSizes"
              :is-minor="isMinor"
              :suggestions="suggestions"
              @check-age="checkAge"
              @search-birth-city="searchBirthCity"
              @select-birth-city="selectBirthCity"
              @search-address="searchAddress"
              @select-address="selectAddress"
            />

            <!-- SECTION 2 : Représentants légaux (si mineur) -->
            <PreInscriptionLegalRepSection
              v-if="isMinor"
              :suggestions="suggestions"
              @copy-adherent-data="copyAdherentData"
              @search-birth-city="searchBirthCity"
              @select-birth-city="selectBirthCity"
              @search-address="searchAddress"
              @select-address="selectAddress"
            />

            <!-- SECTION 3 : Santé & Consentement -->
            <PreInscriptionHealthSection
              v-model:consent-checkbox="consentCheckbox"
            />

            <!-- Message d'erreur -->
            <div class="error-banner ion-margin-top ion-padding-horizontal" v-if="errorMessage">
              <ion-text color="danger">
                <p v-html="errorMessage"></p>
              </ion-text>
            </div>

            <!-- Soumission -->
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

              <p class="privacy-disclaimer">
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
import { provide } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonButton,
  IonIcon,
  IonSpinner,
  IonText,
  onIonViewWillEnter
} from '@ionic/vue';
import { checkmarkCircleOutline } from 'ionicons/icons';
import { useAuthStore } from '@/stores/auth';
import { usePreInscriptionForm } from '@/composables/preinscription/usePreInscriptionForm';
import { useAddressAutocomplete } from '@/composables/preinscription/useAddressAutocomplete';
import { usePreInscriptionApi } from '@/composables/preinscription/usePreInscriptionApi';
import PreInscriptionSuccessCard from '@/components/preinscription/PreInscriptionSuccessCard.vue';
import PreInscriptionIdentitySelector from '@/components/preinscription/PreInscriptionIdentitySelector.vue';
import PreInscriptionMemberSection from '@/components/preinscription/PreInscriptionMemberSection.vue';
import PreInscriptionLegalRepSection from '@/components/preinscription/PreInscriptionLegalRepSection.vue';
import PreInscriptionHealthSection from '@/components/preinscription/PreInscriptionHealthSection.vue';

const authStore = useAuthStore();

const {
  form,
  clothingSizes,
  consentCheckbox,
  isMinor,
  checkAge,
  copyAdherentData,
  resetForm,
} = usePreInscriptionForm();

provide('preInscriptionForm', form);

const {
  suggestions,
  searchBirthCity,
  selectBirthCity,
  searchAddress,
  selectAddress,
} = useAddressAutocomplete(form);

const {
  registrationTargets,
  selectedTargetId,
  completedMemberIds,
  hasLoadedIdentities,
  isSubmitting,
  errorMessage,
  successData,
  loadIdentities,
  prefillAdherent,
  submitForm,
  downloadPdf,
} = usePreInscriptionApi();

onIonViewWillEnter(() => {
  onResetForm();
  loadIdentities((memberId) => prefillAdherent(memberId, form, checkAge));
});

const handlePrefillSelection = (memberId: number) => {
  selectedTargetId.value = memberId;
  if (memberId) {
    prefillAdherent(memberId, form, checkAge);
  } else {
    onResetForm();
  }
};

const onResetForm = () => {
  resetForm();
  selectedTargetId.value = 0;
  errorMessage.value = '';
  successData.value = null;
};

const onSubmit = () => {
  submitForm(form, consentCheckbox.value);
};

const onDownloadPdf = (type: 'health' | 'parental') => {
  downloadPdf(type, form);
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

.error-banner {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
  border-radius: 8px;
  padding: 12px 16px;
  font-weight: 500;
}

.privacy-disclaimer {
  font-size: 0.82em;
  color: var(--ion-color-medium);
  margin-top: 12px;
  line-height: 1.4;
  text-align: center;
}
</style>
