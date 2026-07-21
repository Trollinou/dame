<template>
  <ion-card class="ion-margin-bottom">
    <ion-card-header>
      <ion-card-title>Type de parcours : {{ config.variante }}</ion-card-title>
    </ion-card-header>
    <ion-card-content v-if="config.description">
      <p style="font-size: 1.1rem; line-height: 1.5; margin: 0;">
        {{ config.description }}
      </p>
    </ion-card-content>
  </ion-card>

  <ParcoursViewer
    :fenDepart="config.fen_depart"
    :couleurJoueur="config.couleur_joueur"
    :variante="config.variante"
    :caseDepart="config.case_depart"
    :caseArrivee="config.case_arrivee"
    :shapes="config.shapes || []"
    @success="$emit('success')"
  />
</template>

<script setup lang="ts">
import {
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import ParcoursViewer from '@/components/shared/ParcoursViewer.vue';

defineProps<{
  config: {
    fen_depart: string;
    couleur_joueur: 'white' | 'black';
    description?: string;
    case_depart: string;
    case_arrivee: string;
    variante: string;
    shapes?: Array<{ orig: string; dest?: string; brush: string; [key: string]: any }>;
  };
  id: number;
}>();

defineEmits<{
  (e: 'success'): void;
}>();
</script>
