<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button />
        </ion-buttons>
        <ion-title>Appel à bénévoles</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <DataTable
          :data="benevolats"
          :columns="columns"
          :is-loading="isLoading"
          search-placeholder="Rechercher un appel..."
          :filters="filterConfigs"
          :export-config="exportConfig"
          empty-text="Aucun appel à bénévoles disponible."
          :on-row-click="viewBenevolat"
        >
          <!-- Slots personnalisés pour le rendu mobile -->
          <template #mobile-item="{ row }">
            <ion-label>
              <h2 v-safe-html="row.title.rendered"></h2>
              <p>{{ formatBenevolatDates(row) }}</p>
            </ion-label>
            <div slot="end" style="display: flex; align-items: center; gap: 8px;">
              <ion-badge
                v-if="hasUserVoted(row.id) && !authStore.adminMode"
                color="success"
                :style="isBenevolatExpired(row) ? { opacity: '0.7' } : {}"
              >
                Inscrit
              </ion-badge>
              <ion-badge
                v-if="authStore.adminMode"
                :color="isBenevolatExpired(row) ? 'medium' : 'primary'"
                @click.stop="viewResults(row)"
              >
                {{ getResponseCount(row.id) }} rép.
              </ion-badge>
            </div>
          </template>
        </DataTable>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonBadge,
  IonLabel,
  IonButtons,
  IonMenuButton,
  onIonViewWillEnter
} from '@ionic/vue';
import { useRouter } from 'vue-router';
import { useBenevolatStore, type Benevolat } from '../stores/benevolat';
import { useAuthStore } from '../stores/auth';
import { storeToRefs } from 'pinia';
import {
  DataTable,
  type CustomColumnDef,
  type DataTableFilterConfig,
  type DataTableExportConfig
} from '../components/shared/DataTable';

const router = useRouter();
const benevolatStore = useBenevolatStore();
const authStore = useAuthStore();
const { benevolats, isLoading } = storeToRefs(benevolatStore);
const { getResponseCount, hasUserVoted } = benevolatStore;

const selectedStatus = ref<'all' | 'open' | 'finished'>('all');

const todayStr = (() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
})();

const isBenevolatExpired = (benevolat: Benevolat): boolean => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const dates = data.map((d) => d.date).filter(Boolean);
    if (dates.length === 0) return false;
    const maxDate = dates.reduce((max, d) => (d > max ? d : max), dates[0]);
    return maxDate < todayStr;
  }
  return false;
};

const formatBenevolatDates = (benevolat: Benevolat): string => {
  const data = benevolat.dame_benevolat_data;
  if (Array.isArray(data) && data.length > 0) {
    const firstDate = data[0].date;
    const lastDate = data[data.length - 1].date;
    const format = (d: string) => (d ? d.split('-').reverse().join('/') : '?');
    return `Du ${format(firstDate)} au ${format(lastDate)}`;
  }
  return 'Dates non définies';
};

const viewBenevolat = (benevolat: Benevolat) => {
  if (authStore.adminMode) {
    router.push('/admin/benevolat/' + benevolat.id);
  } else {
    if (authStore.isAuthenticated) {
      router.push('/tabs/benevolat/participation/' + benevolat.id);
    } else {
      router.push({
        path: '/tabs/login',
        query: { message: 'Identification requise pour proposer votre aide.' }
      });
    }
  }
};

const viewResults = (benevolat: Benevolat) => {
  if (authStore.adminMode) {
    router.push('/admin/benevolat/' + benevolat.id);
  } else {
    if (authStore.isAuthenticated) {
      router.push('/tabs/benevolat/participation/' + benevolat.id);
    } else {
      router.push({
        path: '/tabs/login',
        query: { message: 'Identification requise pour proposer votre aide.' }
      });
    }
  }
};

// Configuration des colonnes TanStack Table pour Bénévolat
const columns: CustomColumnDef<Benevolat>[] = [
  {
    id: 'title',
    header: 'Appel / Titre',
    accessorFn: (row) => row.title?.raw || row.title?.rendered || '',
    cell: ({ row }) => h('span', { innerHTML: row.original.title.rendered }),
    enableSorting: true
  },
  {
    id: 'dates',
    header: 'Plage de dates',
    accessorFn: (row) => formatBenevolatDates(row),
    enableSorting: true
  },
  {
    id: 'status',
    header: 'Statut',
    accessorFn: (row) => (isBenevolatExpired(row) ? 'finished' : 'open'),
    cell: ({ row }) => {
      const expired = isBenevolatExpired(row.original);
      return h(
        IonBadge,
        { color: expired ? 'medium' : 'primary' },
        () => (expired ? 'Terminé' : 'En cours')
      );
    },
    filterFn: (row, columnId, filterValue) => {
      if (!filterValue || filterValue === 'all') return true;
      const status = row.getValue(columnId) as string;
      return status === filterValue;
    },
    enableSorting: true
  },
  {
    id: 'responses',
    header: 'Réponses',
    accessorFn: (row) => getResponseCount(row.id),
    cell: ({ row }) => {
      const count = getResponseCount(row.original.id);
      const voted = hasUserVoted(row.original.id);
      if (authStore.adminMode) {
        return h(
          IonBadge,
          { color: isBenevolatExpired(row.original) ? 'medium' : 'primary' },
          () => `${count} rép.`
        );
      }
      if (voted) {
        return h(IonBadge, { color: 'success' }, () => 'Inscrit');
      }
      return '-';
    },
    enableSorting: true
  }
];

// Configuration des filtres facettés pour Bénévolat
const filterConfigs = computed<DataTableFilterConfig[]>(() => [
  {
    id: 'status',
    label: 'Statut',
    defaultValue: selectedStatus.value,
    options: [
      { label: 'Tous les appels', value: 'all' },
      { label: 'Appels en cours', value: 'open' },
      { label: 'Appels terminés', value: 'finished' }
    ]
  }
]);

// Configuration de l'export CSV pour Bénévolat
const exportConfig: DataTableExportConfig<Benevolat> = {
  filename: 'appels_benevoles',
  columns: [
    { header: 'ID', accessor: (b) => b.id },
    { header: 'Titre', accessor: (b) => b.title?.raw || b.title?.rendered || '' },
    { header: 'Plage de dates', accessor: (b) => formatBenevolatDates(b) },
    { header: 'Statut', accessor: (b) => (isBenevolatExpired(b) ? 'Terminé' : 'En cours') },
    { header: 'Nombre de réponses', accessor: (b) => getResponseCount(b.id) }
  ]
};

onIonViewWillEnter(() => {
  benevolatStore.fetchBenevolatsData();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}
</style>
