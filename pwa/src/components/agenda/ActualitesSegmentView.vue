<template>
  <div>
    <div v-if="newsStore.isLoading && newsStore.posts.length === 0" class="ion-text-center ion-padding">
      <ion-spinner name="crescent"></ion-spinner>
      <p>Chargement des actualités...</p>
    </div>

    <div v-else-if="filteredNews.length > 0">
      <ion-card
        v-for="post in filteredNews"
        :key="post.id"
        class="news-card ion-no-margin ion-margin-bottom"
        button
        @click="$emit('go-to-news-detail', post.id)"
      >
        <img
          v-if="getFeaturedImage(post)"
          :src="getFeaturedImage(post) || undefined"
          :alt="post.title.rendered"
          class="featured-image"
          style="width: 100%; height: 200px; object-fit: cover;"
        />
        <ion-card-header>
          <ion-card-subtitle>{{ formatDate(post.date) }}</ion-card-subtitle>
          <ion-card-title v-safe-html="post.title.rendered"></ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <div v-safe-html="post.excerpt.rendered"></div>
        </ion-card-content>
      </ion-card>
    </div>

    <div v-else class="ion-text-center ion-padding">
      <p v-if="searchQuery">Aucune actualité ne correspond à "{{ searchQuery }}".</p>
      <p v-else>Aucune actualité trouvée.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent
} from '@ionic/vue';
import { useNewsStore, type Post } from '@/stores/news';
import { removeAccents } from '@/utils/stringUtils';

const newsStore = useNewsStore();

const props = defineProps<{
  searchQuery: string;
}>();

defineEmits<{
  (e: 'go-to-news-detail', id: number): void;
}>();

const filteredNews = computed(() => {
  if (!props.searchQuery.trim()) return newsStore.posts;
  const query = removeAccents(props.searchQuery.toLowerCase());
  return newsStore.posts.filter((post: Post) =>
    removeAccents((post.title?.rendered || '').toLowerCase()).includes(query)
  );
});

const getFeaturedImage = (post: Post): string | null => {
  return post._embedded?.['wp:featuredmedia']?.[0]?.source_url || null;
};

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
};
</script>

<style scoped>
.news-card {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
</style>
