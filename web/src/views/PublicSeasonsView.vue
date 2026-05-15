<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { publicSeasons, type ApiSeason } from '@/lib/api'

const seasons = ref<ApiSeason[]>([])
const loading = ref(false)

async function loadSeasons(): Promise<void> {
  loading.value = true
  try {
    seasons.value = await publicSeasons()
  } finally {
    loading.value = false
  }
}

onMounted(loadSeasons)
</script>

<template>
  <main class="page">
    <h1>Seasons</h1>
    <p v-if="loading">Loading...</p>
    <ul v-else>
      <li v-for="season in seasons" :key="season.id">
        <RouterLink :to="`/seasons/${season.slug}`">{{ season.name }}</RouterLink>
        - {{ season.status }}
      </li>
    </ul>
  </main>
</template>

<style scoped>
.page { padding: 1.5rem; }
</style>
