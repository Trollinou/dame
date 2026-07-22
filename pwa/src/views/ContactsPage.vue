<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button />
        </ion-buttons>
        <ion-title>Contacts</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <DataTable
          :data="contacts"
          :columns="columns"
          :is-loading="isLoading"
          search-placeholder="Rechercher un contact..."
          :filters="filterConfigs"
          :export-config="exportConfig"
          empty-text="Aucun contact trouvé."
          :on-row-click="goToDetail"
        >
          <!-- Slots personnalisés pour le rendu mobile -->
          <template #mobile-item="{ row }">
            <ion-label>
              <h2 v-safe-html="row.title.rendered"></h2>
              <p v-if="row.meta?._dame_contact_organization">
                {{ row.meta._dame_contact_organization }}
              </p>
            </ion-label>
          </template>
        </DataTable>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { h, ref, computed, watch } from 'vue';
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonLabel,
  IonButtons,
  IonMenuButton,
  onIonViewWillEnter
} from '@ionic/vue';
import { useRouter } from 'vue-router';
import { useContactStore, type Contact } from '../stores/contacts';
import { useReferenceDataStore } from '../stores/referenceData';
import { storeToRefs } from 'pinia';
import {
  DataTable,
  type CustomColumnDef,
  type DataTableFilterConfig,
  type DataTableExportConfig
} from '../components/shared/DataTable';

const router = useRouter();
const contactStore = useContactStore();
const referenceDataStore = useReferenceDataStore();

const { contacts, contactTypes, isLoading } = storeToRefs(contactStore);
const { regions, departments, deptRegionMap } = storeToRefs(referenceDataStore);

const selectedType = ref<number | 'all'>('all');
const selectedRegion = ref<string | 'all'>('all');
const selectedDepartment = ref<string | 'all'>('all');

/**
 * Départements filtrés par la région sélectionnée
 */
const filteredDepartmentsList = computed(() => {
  if (selectedRegion.value === 'all') return departments.value;
  return departments.value.filter(
    (dept) => deptRegionMap.value[dept.code] === selectedRegion.value
  );
});

/**
 * Surveillance du changement de région : réinitialise le département s'il n'est plus valide
 */
watch(selectedRegion, (newRegion) => {
  if (newRegion !== 'all' && selectedDepartment.value !== 'all') {
    const regionOfDept = deptRegionMap.value[selectedDepartment.value];
    if (regionOfDept !== newRegion) {
      selectedDepartment.value = 'all';
    }
  }
});

/**
 * Surveillance du changement de département : ajuste la région correspondante
 */
watch(selectedDepartment, (newDept) => {
  if (newDept !== 'all') {
    const regionOfDept = deptRegionMap.value[newDept];
    if (regionOfDept && selectedRegion.value !== regionOfDept) {
      selectedRegion.value = regionOfDept;
    }
  }
});

const goToDetail = (row: Contact) => {
  router.push('/admin/contact/' + row.id);
};

// Configuration des colonnes TanStack Table pour la vue Contacts
const columns: CustomColumnDef<Contact>[] = [
  {
    id: 'name',
    header: 'Nom',
    accessorFn: (row) => row.title?.raw || row.title?.rendered || '',
    cell: ({ row }) => h('span', { innerHTML: row.original.title.rendered }),
    enableSorting: true
  },
  {
    id: 'organization',
    header: 'Organisme',
    accessorFn: (row) => row.meta?._dame_contact_organization || '-',
    enableSorting: true
  },
  {
    id: 'contact-types',
    header: 'Type',
    accessorFn: (row) => row['contact-types'],
    cell: ({ row }) => {
      const typeIds = row.original['contact-types'] || [];
      const names = typeIds
        .map((id) => contactTypes.value.find((t) => t.id === id)?.name)
        .filter(Boolean);
      return names.length > 0 ? names.join(', ') : '-';
    },
    filterFn: (row, columnId, filterValue) => {
      if (!filterValue || filterValue === 'all') return true;
      const typeIds = row.getValue(columnId) as number[];
      return Array.isArray(typeIds) && typeIds.includes(Number(filterValue));
    },
    enableHiding: true
  },
  {
    id: 'region',
    header: 'Région',
    accessorFn: (row) => row.meta?._dame_contact_region || '-',
    filterFn: (row, columnId, filterValue) => {
      if (!filterValue || filterValue === 'all') return true;
      return row.original.meta?._dame_contact_region === String(filterValue);
    },
    enableSorting: true
  },
  {
    id: 'department',
    header: 'Département',
    accessorFn: (row) => row.meta?._dame_contact_department || '-',
    filterFn: (row, columnId, filterValue) => {
      if (!filterValue || filterValue === 'all') return true;
      return row.original.meta?._dame_contact_department === String(filterValue);
    },
    enableSorting: true
  },
  {
    id: 'email',
    header: 'E-mail',
    accessorFn: (row) => row.meta?._dame_contact_email || '-',
    enableSorting: true
  },
  {
    id: 'phone',
    header: 'Téléphone',
    accessorFn: (row) => row.meta?._dame_contact_phone || '-',
    enableSorting: true
  }
];

// Configuration des filtres facettés pour Contacts
const filterConfigs = computed<DataTableFilterConfig[]>(() => [
  {
    id: 'contact-types',
    label: 'Type',
    defaultValue: selectedType.value,
    options: [
      { label: 'Tous les types', value: 'all' },
      ...contactTypes.value.map((t) => ({ label: t.name, value: t.id }))
    ]
  },
  {
    id: 'region',
    label: 'Région',
    defaultValue: selectedRegion.value,
    options: [
      { label: 'Toutes les régions', value: 'all' },
      ...regions.value.map((r) => ({ label: r.name, value: r.code }))
    ]
  },
  {
    id: 'department',
    label: 'Dept.',
    defaultValue: selectedDepartment.value,
    options: [
      { label: 'Tous les départements', value: 'all' },
      ...filteredDepartmentsList.value.map((d) => ({ label: d.name, value: d.code }))
    ]
  }
]);

// Configuration de l'export CSV pour Contacts
const exportConfig: DataTableExportConfig<Contact> = {
  filename: 'contacts',
  columns: [
    { header: 'ID', accessor: (c) => c.id },
    { header: 'Nom', accessor: (c) => c.title?.raw || c.title?.rendered || '' },
    { header: 'Organisme', accessor: (c) => c.meta?._dame_contact_organization || '' },
    { header: 'Rôle', accessor: (c) => c.meta?._dame_contact_role || '' },
    { header: 'Région', accessor: (c) => c.meta?._dame_contact_region || '' },
    { header: 'Département', accessor: (c) => c.meta?._dame_contact_department || '' },
    { header: 'Ville', accessor: (c) => c.meta?._dame_contact_city || '' },
    { header: 'E-mail', accessor: (c) => c.meta?._dame_contact_email || '' },
    { header: 'Téléphone', accessor: (c) => c.meta?._dame_contact_phone || '' }
  ]
};

onIonViewWillEnter(async () => {
  contactStore.fetchContacts();
  contactStore.fetchContactTypes();
  referenceDataStore.fetchRegions();
  referenceDataStore.fetchDepartments();
  referenceDataStore.fetchMapping();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}
</style>
