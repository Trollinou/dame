<template>
  <div class="form-section animate-fade-in">
    <h3 class="section-title">Représentant Légal 1</h3>
    <div class="copy-adh-container ion-padding-bottom">
      <ion-button size="small" fill="outline" color="secondary" @click="$emit('copy-adherent-data', 1)">
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
          @ionInput="(e: CustomEvent) => $emit('search-birth-city', e.detail.value || '', 1)"
        ></ion-input>
      </ion-item>
      <div v-if="suggestions.rep1BirthCity.length > 0" class="suggestions-outer-container">
        <ul class="suggestions-list">
          <li
            v-for="s in suggestions.rep1BirthCity"
            :key="s"
            @click="$emit('select-birth-city', s, 1)"
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

      <ion-item lines="none" class="optin-item ion-text-wrap" style="--background: transparent; margin-top: -5px; margin-bottom: 5px;">
        <ion-checkbox
          class="ion-text-wrap"
          v-model="form.dame_legal_rep_1_refuses_comms"
          style="--size: 18px; font-size: 0.85em; --border-radius: 4px;"
        >
          <span style="white-space: normal; line-height: 1.3; display: block; color: var(--ion-color-medium); font-size: 0.9em; margin-left: 8px;">
            Je m'oppose à la réception des e-mails d'information de l'association.
          </span>
        </ion-checkbox>
      </ion-item>

      <ion-item>
        <ion-input
          v-model="form.dame_legal_rep_1_profession"
          label="Profession"
          label-placement="stacked"
        ></ion-input>
      </ion-item>

      <ion-item>
        <ion-input
          v-model="form.dame_legal_rep_1_address_1"
          label="Adresse *"
          label-placement="stacked"
          required
          @ionInput="(e: CustomEvent) => $emit('search-address', e.detail.value || '', 1)"
        ></ion-input>
      </ion-item>
      <div v-if="suggestions.rep1Address.length > 0" class="suggestions-outer-container">
        <ul class="suggestions-list">
          <li
            v-for="s in suggestions.rep1Address"
            :key="s.fulltext"
            @click="$emit('select-address', s, 1)"
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
      <ion-button size="small" fill="outline" color="secondary" @click="$emit('copy-adherent-data', 2)">
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
          @ionInput="(e: CustomEvent) => $emit('search-birth-city', e.detail.value || '', 2)"
        ></ion-input>
      </ion-item>
      <div v-if="suggestions.rep2BirthCity.length > 0" class="suggestions-outer-container">
        <ul class="suggestions-list">
          <li
            v-for="s in suggestions.rep2BirthCity"
            :key="s"
            @click="$emit('select-birth-city', s, 2)"
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

      <ion-item lines="none" class="optin-item ion-text-wrap" style="--background: transparent; margin-top: -5px; margin-bottom: 5px;">
        <ion-checkbox
          class="ion-text-wrap"
          v-model="form.dame_legal_rep_2_refuses_comms"
          style="--size: 18px; font-size: 0.85em; --border-radius: 4px;"
        >
          <span style="white-space: normal; line-height: 1.3; display: block; color: var(--ion-color-medium); font-size: 0.9em; margin-left: 8px;">
            Je m'oppose à la réception des e-mails d'information de l'association.
          </span>
        </ion-checkbox>
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
          @ionInput="(e: CustomEvent) => $emit('search-address', e.detail.value || '', 2)"
        ></ion-input>
      </ion-item>
      <div v-if="suggestions.rep2Address.length > 0" class="suggestions-outer-container">
        <ul class="suggestions-list">
          <li
            v-for="s in suggestions.rep2Address"
            :key="s.fulltext"
            @click="$emit('select-address', s, 2)"
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
</template>

<script setup lang="ts">
import { inject } from 'vue';
import {
  IonList,
  IonItem,
  IonInput,
  IonCheckbox,
  IonButton
} from '@ionic/vue';
import type { PreInscriptionFormData } from '@/composables/preinscription/usePreInscriptionForm';
import type { GeoAddressResult } from '@/utils/geoApi';

const form = inject<PreInscriptionFormData>('preInscriptionForm')!;

defineProps<{
  suggestions: {
    rep1BirthCity: string[];
    rep2BirthCity: string[];
    rep1Address: GeoAddressResult[];
    rep2Address: GeoAddressResult[];
  };
}>();

defineEmits<{
  (e: 'copy-adherent-data', repNum: number): void;
  (e: 'search-birth-city', query: string, repNum: number): void;
  (e: 'select-birth-city', city: string, repNum: number): void;
  (e: 'search-address', query: string, repNum: number): void;
  (e: 'select-address', feature: GeoAddressResult, repNum: number): void;
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

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
