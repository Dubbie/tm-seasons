<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import {
  adminMaps,
  adminSeason,
  attachMapToSeason,
  removeSeasonMap,
  updateSeasonMap,
  type ApiMap,
  type ApiSeason,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const availableMaps = ref<ApiMap[]>([])
const selectedMapId = ref<number | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

const seasonId = computed(() => Number(route.params.id))

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, maps] = await Promise.all([adminSeason(seasonId.value), adminMaps()])
    season.value = seasonData
    availableMaps.value = maps
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load season'
  } finally {
    loading.value = false
  }
}

async function handleAttach(): Promise<void> {
  if (!selectedMapId.value) return

  try {
    season.value = await attachMapToSeason(seasonId.value, selectedMapId.value)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Attach failed'
  }
}

async function move(map: ApiMap, delta: number): Promise<void> {
  const nextIndex = Math.max(0, (map.season_pivot?.order_index ?? 0) + delta)
  season.value = await updateSeasonMap(seasonId.value, map.id, { order_index: nextIndex })
}

async function toggleMapActive(map: ApiMap): Promise<void> {
  season.value = await updateSeasonMap(seasonId.value, map.id, {
    is_active: !(map.season_pivot?.is_active ?? true),
  })
}

async function detach(map: ApiMap): Promise<void> {
  await removeSeasonMap(seasonId.value, map.id)
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <main class="page">
    <h1>Season Edit</h1>
    <p v-if="season"><strong>{{ season.name }}</strong> ({{ season.slug }})</p>

    <div class="attach-row">
      <select v-model.number="selectedMapId">
        <option :value="null">Select imported map</option>
        <option v-for="map in availableMaps" :key="map.id" :value="map.id">{{ map.name || map.uid }}</option>
      </select>
      <button type="button" @click="handleAttach">Attach Map</button>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <section v-if="season?.maps?.length" class="list">
      <article v-for="map in season.maps" :key="map.id" class="card">
        <h2>{{ map.name || map.uid }}</h2>
        <p>Order: {{ map.season_pivot?.order_index ?? 0 }} | Active: {{ map.season_pivot?.is_active ? 'yes' : 'no' }}</p>
        <div class="row">
          <button type="button" @click="move(map, -1)">Move Up</button>
          <button type="button" @click="move(map, 1)">Move Down</button>
          <button type="button" @click="toggleMapActive(map)">Toggle Active</button>
          <button type="button" @click="detach(map)">Remove</button>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.page { padding: 1.5rem; }
.attach-row { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.list { display: grid; gap: 0.75rem; }
.card { border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; }
.row { display: flex; gap: 0.5rem; }
.error { color: #b00020; }
</style>
