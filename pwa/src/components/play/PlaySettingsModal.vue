<template>
  <ion-modal
    :is-open="isOpen"
    @didDismiss="$emit('update:isOpen', false)"
    :initial-breakpoint="0.5"
    :breakpoints="[0, 0.5, 0.8]"
  >
    <ion-header>
      <ion-toolbar>
        <ion-title>Nouvelle Partie</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="$emit('update:isOpen', false)">Annuler</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list lines="none">
        <ion-item>
          <ion-label position="stacked">Ta couleur</ion-label>
          <ion-select v-model="gameSettings.playerColor" interface="popover">
            <ion-select-option value="white">Blancs</ion-select-option>
            <ion-select-option value="black">Noirs</ion-select-option>
            <ion-select-option value="random">Aléatoire</ion-select-option>
          </ion-select>
        </ion-item>

        <ion-item class="ion-margin-top">
          <ion-label position="stacked">Cadence (Pendule)</ion-label>
          <ion-select v-model="gameSettings.clockPreset" interface="popover">
            <ion-select-option value="none">Sans pendule</ion-select-option>
            <ion-select-option value="1+0">1 min (Bullet)</ion-select-option>
            <ion-select-option value="3+2">3 min + 2 s (Blitz)</ion-select-option>
            <ion-select-option value="5+0">5 min KO (Blitz)</ion-select-option>
            <ion-select-option value="10+5">10 min + 5 s (Rapide)</ion-select-option>
            <ion-select-option value="15+10">15 min + 10 s (Rapide)</ion-select-option>
          </ion-select>
        </ion-item>

        <ion-item class="ion-margin-top">
          <ion-label position="stacked">Niveau de l'IA (ELO : {{ gameSettings.level }})</ion-label>
          <ion-range
            v-model="gameSettings.level"
            :min="1320"
            :max="2800"
            :step="10"
            snaps
            ticks
            color="secondary"
          >
            <ion-icon slot="start" :icon="handRightOutline" size="small"></ion-icon>
            <ion-icon slot="end" :icon="handRightOutline"></ion-icon>
          </ion-range>
        </ion-item>

        <ion-item class="ion-margin-top">
          <ion-label>Indicateur matériel</ion-label>
          <ion-toggle
            :model-value="showMaterialIndicator"
            @update:model-value="$emit('update:showMaterialIndicator', $event)"
          ></ion-toggle>
        </ion-item>

        <ion-button expand="block" class="ion-margin-top" @click="$emit('start-game')">
          Lancer la partie
        </ion-button>
      </ion-list>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import { inject } from 'vue';
import {
  IonModal,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonButton,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonSelect,
  IonSelectOption,
  IonRange,
  IonIcon,
  IonToggle
} from '@ionic/vue';
import { handRightOutline } from 'ionicons/icons';
import type { ClockPreset } from '@/composables/play/usePlayClock';

interface GameSettingsState {
  playerColor: 'white' | 'black' | 'random';
  clockPreset: ClockPreset;
  level: number;
}

const gameSettings = inject<GameSettingsState>('gameSettings')!;

defineProps<{
  isOpen: boolean;
  showMaterialIndicator: boolean;
}>();

defineEmits<{
  (e: 'update:isOpen', val: boolean): void;
  (e: 'update:showMaterialIndicator', val: boolean): void;
  (e: 'start-game'): void;
}>();
</script>
