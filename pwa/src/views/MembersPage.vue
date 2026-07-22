<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button />
        </ion-buttons>
        <ion-title>Adhérents</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <DataTable
          :data="members"
          :columns="columns"
          :is-loading="isLoading"
          search-placeholder="Rechercher un adhérent..."
          :filters="filterConfigs"
          :export-config="exportConfig"
          empty-text="Aucun adhérent trouvé."
          :on-row-click="goToDetail"
        >
          <!-- Slots personnalisés pour le rendu des cellules ou l'affichage mobile -->
          <template #mobile-item="{ row }">
            <ion-label>
              <h2 v-safe-html="row.title.rendered"></h2>
              <p v-if="row.meta?._dame_license_number">
                Licence : {{ row.meta._dame_license_number }}
              </p>
            </ion-label>
            <ion-badge v-if="row.dame_age_category" slot="end" color="light">
              {{ row.dame_age_category }}
            </ion-badge>
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
import { useMemberStore, type Member } from '../stores/members';
import { storeToRefs } from 'pinia';
import {
  DataTable,
  type CustomColumnDef,
  type DataTableFilterConfig,
  type DataTableExportConfig
} from '../components/shared/DataTable';

const router = useRouter();
const memberStore = useMemberStore();
const { members, seasons, isLoading } = storeToRefs(memberStore);
const selectedSeason = ref<number | 'all'>('all');

const AGE_CATEGORY_ORDER = [
  'U8', 'U8F', 'U10', 'U10F', 'U12', 'U12F', 'U14', 'U14F', 'U16', 'U16F',
  'U18', 'U18F', 'U20', 'U20F', 'Sénior', 'SéniorF', 'Sénior+', 'Sénior+F', 'Vétéran', 'VétéranF'
];

const goToDetail = (row: Member) => {
  router.push('/admin/members/' + row.id);
};

// Configuration des colonnes TanStack Table
const columns: CustomColumnDef<Member>[] = [
  {
    id: 'name',
    header: 'Nom',
    accessorFn: (row) => row.title?.raw || row.title?.rendered || '',
    cell: ({ row }) => h('span', { innerHTML: row.original.title.rendered }),
    enableSorting: true
  },
  {
    id: 'dame_age_category',
    header: 'Catégorie',
    accessorFn: (row) => row.dame_age_category || '',
    cell: ({ getValue }) => {
      const cat = getValue() as string;
      return cat ? h(IonBadge, { color: 'light' }, () => cat) : '-';
    },
    enableSorting: true,
    sortingFn: (rowA, rowB, columnId) => {
      const catA = (rowA.getValue(columnId) as string) || '';
      const catB = (rowB.getValue(columnId) as string) || '';
      const idxA = AGE_CATEGORY_ORDER.indexOf(catA);
      const idxB = AGE_CATEGORY_ORDER.indexOf(catB);
      if (idxA !== -1 && idxB !== -1) return idxA - idxB;
      if (idxA === -1) return 1;
      if (idxB === -1) return -1;
      return catA.localeCompare(catB);
    }
  },
  {
    id: 'seasons',
    header: 'Saisons',
    accessorFn: (row) => row.seasons,
    filterFn: (row, columnId, filterValue) => {
      if (!filterValue || filterValue === 'all') return true;
      const seasonsList = row.getValue(columnId) as number[];
      return Array.isArray(seasonsList) && seasonsList.includes(Number(filterValue));
    },
    enableHiding: true
  },
  {
    id: 'license',
    header: 'Licence',
    accessorFn: (row) => row.meta?._dame_license_number || '-',
    enableSorting: true
  },
  {
    id: 'elo',
    header: 'Elo Standard',
    accessorFn: (row) => row.meta?._dame_elo_standard || '-',
    enableSorting: true
  },
  {
    id: 'email',
    header: 'E-mail',
    accessorFn: (row) => row.meta?._dame_email || '-',
    enableSorting: true
  }
];

// Configuration des filtres facettés
const filterConfigs = computed<DataTableFilterConfig[]>(() => [
  {
    id: 'seasons',
    label: 'Saison',
    defaultValue: selectedSeason.value,
    options: [
      { label: 'Toutes les saisons', value: 'all' },
      ...seasons.value.map((s) => ({ label: s.name, value: s.id }))
    ]
  }
]);

// Configuration de l'export CSV
const exportConfig: DataTableExportConfig<Member> = {
  filename: 'adherents',
  columns: [
    { header: 'ID', accessor: (m) => m.id },
    { header: 'Nom', accessor: (m) => m.title?.raw || m.title?.rendered || '' },
    { header: "Catégorie d'âge", accessor: (m) => m.dame_age_category || '' },
    { header: 'Licence', accessor: (m) => m.meta?._dame_license_number || '' },
    { header: 'Elo Standard', accessor: (m) => m.meta?._dame_elo_standard || '' },
    { header: 'E-mail', accessor: (m) => m.meta?._dame_email || '' },
    { header: 'Téléphone', accessor: (m) => m.meta?._dame_phone_number || '' }
  ]
};

onIonViewWillEnter(async () => {
  memberStore.fetchMembers();
  await memberStore.fetchSeasons();
  if (selectedSeason.value === 'all' && seasons.value.length > 0) {
    selectedSeason.value = seasons.value[0].id;
  }
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}
</style>
