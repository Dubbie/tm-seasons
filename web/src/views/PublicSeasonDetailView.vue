<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { publicSeason, type ApiSeason } from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const loading = ref(false)

async function loadSeason(): Promise<void> {
  loading.value = true
  try {
    season.value = await publicSeason(String(route.params.slug))
  } finally {
    loading.value = false
  }
}

onMounted(loadSeason)
</script>

<template>
  <main class="page">
    <h1>Season Detail</h1>
    <p v-if="loading">Loading...</p>
    <template v-else-if="season">
      <h2>{{ season.name }}</h2>
      <p>{{ season.description || 'No description yet' }}</p>
      <h3>Maps</h3>
      <ul>
        <li v-for="map in season.maps || []" :key="map.id">
          {{ map.season_pivot?.order_index ?? 0 }} - {{ map.name || map.uid }}
          <span v-if="map.season_pivot?.is_active === false">(inactive)</span>
        </li>
      </ul>
      <p>Leaderboard placeholders coming in a later milestone.</p>
    </template>
  </main>
</template>

<style scoped>
.page { padding: 1.5rem; }
</style>
