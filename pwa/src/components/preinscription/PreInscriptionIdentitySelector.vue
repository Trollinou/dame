<template>
  <ion-card class="ion-no-margin ion-margin-bottom identity-select-card">
    <ion-card-content>
      <ion-item lines="none">
        <ion-select
          label="Pour qui souhaitez-vous faire la préinscription ?"
          label-placement="stacked"
          placeholder="Sélectionner..."
          @ionChange="$emit('select-target', $event.detail.value)"
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
</template>

<script setup lang="ts">
import {
  IonCard,
  IonCardContent,
  IonItem,
  IonSelect,
  IonSelectOption
} from '@ionic/vue';
import type { RegistrationTarget } from '@/composables/preinscription/usePreInscriptionApi';

defineProps<{
  registrationTargets: RegistrationTarget[];
  selectedTargetId: number;
  completedMemberIds: number[];
}>();

defineEmits<{
  (e: 'select-target', memberId: number): void;
}>();
</script>

<style scoped>
.identity-select-card {
  --background: var(--ion-color-primary-contrast, #f4f5f8);
  border-left: 4px solid var(--ion-color-primary);
}
</style>
