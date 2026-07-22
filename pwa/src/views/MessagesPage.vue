<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button />
        </ion-buttons>
        <ion-title>Messages</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="safe-area-wrapper">
        <DataTable
          :data="messages"
          :columns="columns"
          :is-loading="isLoading"
          search-placeholder="Rechercher un message..."
          :export-config="exportConfig"
          empty-text="Aucun message disponible."
          :on-row-click="viewMessage"
        >
          <!-- Slots personnalisés pour le rendu mobile -->
          <template #mobile-item="{ row }">
            <ion-label>
              <h2 v-safe-html="row.title.rendered"></h2>
              <p>{{ formatMessageDate(row.date) }}</p>
            </ion-label>
          </template>
        </DataTable>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { h } from 'vue';
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
import { useMessageStore, type Message } from '../stores/messages';
import { storeToRefs } from 'pinia';
import {
  DataTable,
  type CustomColumnDef,
  type DataTableExportConfig
} from '../components/shared/DataTable';

const router = useRouter();
const messageStore = useMessageStore();
const { messages, isLoading } = storeToRefs(messageStore);

const formatMessageDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return `Le ${date.toLocaleDateString('fr-FR')}`;
};

const viewMessage = (message: Message) => {
  router.push('/admin/message/' + message.id);
};

// Configuration des colonnes TanStack Table pour Messages
const columns: CustomColumnDef<Message>[] = [
  {
    id: 'title',
    header: 'Sujet / Titre',
    accessorFn: (row) => row.title?.raw || row.title?.rendered || '',
    cell: ({ row }) => h('span', { innerHTML: row.original.title.rendered }),
    enableSorting: true
  },
  {
    id: 'date',
    header: 'Date',
    accessorFn: (row) => row.date,
    cell: ({ getValue }) => {
      const val = getValue() as string;
      return val ? formatMessageDate(val) : '-';
    },
    enableSorting: true,
    sortingFn: (rowA, rowB, columnId) => {
      const dateA = new Date((rowA.getValue(columnId) as string) || 0).getTime();
      const dateB = new Date((rowB.getValue(columnId) as string) || 0).getTime();
      return dateA - dateB;
    }
  },
  {
    id: 'sent_count',
    header: 'Envoyés',
    accessorFn: (row) => row.report?.stats?.sent ?? '-',
    enableSorting: true
  },
  {
    id: 'opened_count',
    header: 'Ouverts',
    accessorFn: (row) => row.report?.stats?.opened ?? '-',
    enableSorting: true
  },
  {
    id: 'rate',
    header: "Taux d'ouverture",
    accessorFn: (row) => {
      const rate = row.report?.stats?.rate;
      return rate !== undefined ? `${rate}%` : '-';
    },
    enableSorting: true
  }
];

// Configuration d'export CSV pour Messages
const exportConfig: DataTableExportConfig<Message> = {
  filename: 'messages',
  columns: [
    { header: 'ID', accessor: (m) => m.id },
    { header: 'Titre', accessor: (m) => m.title?.raw || m.title?.rendered || '' },
    { header: 'Date', accessor: (m) => m.date ? new Date(m.date).toLocaleDateString('fr-FR') : '' },
    { header: 'Envoyés', accessor: (m) => m.report?.stats?.sent ?? 0 },
    { header: 'Ouverts', accessor: (m) => m.report?.stats?.opened ?? 0 },
    { header: "Taux d'ouverture (%)", accessor: (m) => m.report?.stats?.rate ?? 0 }
  ]
};

onIonViewWillEnter(() => {
  messageStore.fetchMessages();
});
</script>

<style scoped>
.safe-area-wrapper {
  padding-left: var(--ion-safe-area-left, 0);
  padding-right: var(--ion-safe-area-right, 0);
}
</style>
