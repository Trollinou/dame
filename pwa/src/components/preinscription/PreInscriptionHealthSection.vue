<template>
  <div class="form-section ion-margin-top">
    <h3 class="section-title">Questionnaire de santé</h3>

    <div class="health-info-box ion-padding-bottom">
      <p>
        Veuillez consulter le questionnaire officiel. Si vous répondez "OUI" à au moins une question, vous devrez fournir un certificat médical.
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

      <ion-item style="margin-top: 15px;" class="ion-text-wrap">
        <ion-checkbox
          class="ion-text-wrap"
          :model-value="consentCheckbox"
          @update:model-value="$emit('update:consentCheckbox', $event)"
          required
          justify="start"
          label-placement="end"
          style="--size: 20px;"
        >
          <span class="consent-text" style="white-space: normal; display: block;">
            En cochant cette case, je reconnais avoir pris connaissance du règlement intérieur de l’Association Échiquier Lédonien et m’engage à le respecter. *
          </span>
        </ion-checkbox>
      </ion-item>
    </ion-list>
  </div>
</template>

<script setup lang="ts">
import { inject } from 'vue';
import {
  IonList,
  IonItem,
  IonButton,
  IonCheckbox
} from '@ionic/vue';
import type { PreInscriptionFormData } from '@/composables/preinscription/usePreInscriptionForm';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL;
const siteUrl = apiBaseUrl.replace(/\/wp-json\/?$/, '');

const form = inject<PreInscriptionFormData>('preInscriptionForm')!;

defineProps<{
  consentCheckbox: boolean;
}>();

defineEmits<{
  (e: 'update:consentCheckbox', val: boolean): void;
}>();
</script>

<style scoped>
.section-title {
  font-size: 1.25em;
  font-weight: 700;
  color: var(--ion-color-primary);
  margin-top: 25px;
  margin-bottom: 12px;
  border-bottom: 2px solid var(--ion-color-step-100, #e0e0e0);
  padding-bottom: 6px;
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

.radio-options.vertical {
  display: flex;
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
</style>
