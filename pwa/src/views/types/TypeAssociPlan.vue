<template>
  <div class="exercice-type-associ-plan">
    <!-- Phase 1 : Jeu (Matching) -->
    <div v-if="phase === 'matching'" class="phase-container">
      <MatchingViewer
        :paires="config.paires"
        @success="passerEnRevision"
      />
    </div>

    <!-- Phase 2 : Révision (Affichage PGN) -->
    <div v-else-if="phase === 'review'" class="phase-container review-phase">
      <div class="instruction-header">
        <span class="icon">📖</span>
        <h2>Révisez les 4 positions ci-dessous</h2>
        <p>Cliquez sur chaque accordéon pour revoir le PGN associé.</p>
      </div>

      <ion-accordion-group class="positions-accordion-group">
        <ion-accordion
          v-for="(paire, index) in config.paires"
          :key="index"
          :value="`pos-${index}`"
          class="position-accordion"
        >
          <ion-item
            slot="header"
            class="accordion-item-header"
            :style="{
              '--background': colors[index].bg,
              'border-left': `5px solid ${colors[index].border}`
            }"
          >
            <ion-label class="accordion-label">
              <span
                class="pos-badge"
                :style="{
                  'background-color': colors[index].border,
                  'color': '#fff'
                }"
              >
                Pos {{ index + 1 }}
              </span>
              <span class="pos-desc" :style="{ 'color': colors[index].text }">
                {{ resumeDescription(paire.description) }}
              </span>
            </ion-label>
          </ion-item>
          <div slot="content" class="accordion-content">
            <PgnViewer :pgnString="paire.pgn_data" />
          </div>
        </ion-accordion>
      </ion-accordion-group>

      <div class="action-footer ion-text-center">
        <ion-button
          expand="block"
          color="primary"
          class="finish-btn"
          @click="terminerExercice"
        >
          Terminer l'exercice
        </ion-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import {
  IonAccordionGroup,
  IonAccordion,
  IonItem,
  IonLabel,
  IonButton
} from '@ionic/vue';
import { useApprentissageStore } from '@/stores/apprentissage';
import MatchingViewer from '@/components/shared/MatchingViewer.vue';
import PgnViewer from '@/components/shared/PgnViewer.vue';

interface Paire {
  fen: string;
  couleur_joueur: 'white' | 'black';
  description: string;
  pgn_data: string;
}

interface ConfigAssociPlan {
  paires: Paire[];
}

const props = defineProps<{
  config: ConfigAssociPlan;
  id: number;
}>();

const emit = defineEmits<{
  (e: 'success'): void;
}>();

const store = useApprentissageStore();
const phase = ref<'matching' | 'review'>('matching');

// Palette de couleurs partagée avec MatchingViewer
const colors = [
  { name: 'blue', border: '#3880ff', bg: 'rgba(56, 128, 255, 0.04)', text: '#3880ff' },
  { name: 'purple', border: '#af47ff', bg: 'rgba(175, 71, 255, 0.04)', text: '#af47ff' },
  { name: 'orange', border: '#e67e22', bg: 'rgba(230, 126, 34, 0.04)', text: '#e67e22' },
  { name: 'pink', border: '#ff375f', bg: 'rgba(255, 55, 95, 0.04)', text: '#ff375f' }
];

const passerEnRevision = () => {
  phase.value = 'review';
};

const resumeDescription = (desc: string): string => {
  if (desc.length <= 45) return desc;
  return desc.substring(0, 42) + '...';
};

const terminerExercice = () => {
  store.validerExercice(props.id);
  emit('success');
};
</script>

<style scoped>
.exercice-type-associ-plan {
  width: 100%;
}

.phase-container {
  width: 100%;
  animation: fadeIn 0.4s ease;
}

.review-phase {
  padding: 12px;
}

.instruction-header {
  text-align: center;
  margin-bottom: 20px;
  background: var(--ion-color-step-50, #fcfcfc);
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.instruction-header .icon {
  font-size: 2.2rem;
  display: block;
  margin-bottom: 8px;
}

.instruction-header h2 {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 0 0 6px 0;
  color: var(--ion-color-step-900, #222);
}

.instruction-header p {
  font-size: 0.9rem;
  margin: 0;
  color: var(--ion-color-step-600, #666);
}

.positions-accordion-group {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  margin-bottom: 24px;
}

.position-accordion {
  border-bottom: 1px solid var(--ion-color-step-150, #eaeaea);
}

.position-accordion:last-child {
  border-bottom: none;
}

.accordion-item-header {
  --padding-start: 12px;
}

.accordion-label {
  display: flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

.pos-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  flex-shrink: 0;
}

.pos-desc {
  font-size: 0.92rem;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
}

.accordion-content {
  background-color: var(--ion-color-step-100, #f4f5f8);
  padding: 12px;
}

.action-footer {
  margin-top: 16px;
}

.finish-btn {
  --border-radius: 10px;
  font-weight: 600;
  font-size: 1.05rem;
  height: 48px;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
