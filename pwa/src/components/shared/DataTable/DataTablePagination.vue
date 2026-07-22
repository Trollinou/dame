<template>
  <div class="datatable-pagination ion-padding-horizontal">
    <div class="pagination-info">
      <span class="text-subtle">
        Affichage de <strong>{{ startRow }}</strong> à <strong>{{ endRow }}</strong> sur <strong>{{ totalRows }}</strong>
      </span>
    </div>

    <div class="pagination-controls">
      <div class="page-size-selector">
        <ion-select
          :value="pageSize"
          interface="popover"
          aria-label="Taille de page"
          class="size-select"
          @ionChange="onPageSizeChange"
        >
          <ion-select-option :value="10">10 par page</ion-select-option>
          <ion-select-option :value="25">25 par page</ion-select-option>
          <ion-select-option :value="50">50 par page</ion-select-option>
          <ion-select-option :value="100">100 par page</ion-select-option>
        </ion-select>
      </div>

      <div class="page-buttons">
        <ion-button
          fill="clear"
          size="small"
          :disabled="!canPreviousPage"
          @click="onFirstPage"
        >
          <ion-icon :icon="chevronBackOutline" />
          <ion-icon :icon="chevronBackOutline" class="stacked-icon" />
        </ion-button>

        <ion-button
          fill="clear"
          size="small"
          :disabled="!canPreviousPage"
          @click="onPrevPage"
        >
          <ion-icon :icon="chevronBackOutline" />
        </ion-button>

        <span class="page-indicator">
          {{ pageIndex + 1 }} / {{ pageCount || 1 }}
        </span>

        <ion-button
          fill="clear"
          size="small"
          :disabled="!canNextPage"
          @click="onNextPage"
        >
          <ion-icon :icon="chevronForwardOutline" />
        </ion-button>

        <ion-button
          fill="clear"
          size="small"
          :disabled="!canNextPage"
          @click="onLastPage"
        >
          <ion-icon :icon="chevronForwardOutline" />
          <ion-icon :icon="chevronForwardOutline" class="stacked-icon" />
        </ion-button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { computed } from 'vue';
import { IonButton, IonIcon, IonSelect, IonSelectOption } from '@ionic/vue';
import { chevronBackOutline, chevronForwardOutline } from 'ionicons/icons';
import type { Table } from '@tanstack/vue-table';

const props = defineProps<{
  table: Table<TData>;
}>();

const pageIndex = computed(() => props.table.getState().pagination.pageIndex);
const pageSize = computed(() => props.table.getState().pagination.pageSize);
const totalRows = computed(() => props.table.getFilteredRowModel().rows.length);
const pageCount = computed(() => props.table.getPageCount());

const canPreviousPage = computed(() => props.table.getCanPreviousPage());
const canNextPage = computed(() => props.table.getCanNextPage());

const startRow = computed(() => {
  if (totalRows.value === 0) return 0;
  return pageIndex.value * pageSize.value + 1;
});

const endRow = computed(() => {
  return Math.min((pageIndex.value + 1) * pageSize.value, totalRows.value);
});

const onFirstPage = () => props.table.setPageIndex(0);
const onPrevPage = () => props.table.previousPage();
const onNextPage = () => props.table.nextPage();
const onLastPage = () => props.table.setPageIndex(pageCount.value - 1);

const onPageSizeChange = (e: CustomEvent) => {
  const val = Number(e.detail.value);
  if (val) {
    props.table.setPageSize(val);
  }
};
</script>

<style scoped>
.datatable-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 12px;
  padding-bottom: 12px;
  border-top: 1px solid var(--ion-color-step-150, rgba(0, 0, 0, 0.08));
  font-size: 0.875rem;
}

.text-subtle {
  color: var(--ion-color-medium);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 16px;
}

.size-select {
  --padding-start: 8px;
  --padding-end: 8px;
  font-size: 0.85rem;
}

.page-buttons {
  display: flex;
  align-items: center;
  gap: 4px;
}

.page-indicator {
  padding: 0 8px;
  font-weight: 600;
  color: var(--ion-color-dark);
}

.stacked-icon {
  margin-left: -8px;
}
</style>
