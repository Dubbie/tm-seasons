<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
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
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Season Edit</h1>
        <p v-if="season" class="mt-1 text-sm text-slate-600">
          <strong>{{ season.name }}</strong> ({{ season.slug }})
        </p>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
          <UiSelect
            id="season-map-select"
            v-model="selectedMapId"
            label="Attach imported map"
            class="max-w-xl"
          >
            <option :value="''">Select imported map</option>
            <option v-for="map in availableMaps" :key="map.id" :value="String(map.id)">{{ map.name || map.uid }}</option>
          </UiSelect>
          <UiButton variant="secondary" @click="handleAttach">Attach Map</UiButton>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <div v-if="season?.maps?.length" class="grid gap-3">
        <UiCard v-for="map in season.maps" :key="map.id">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">{{ map.name || map.uid }}</h2>
              <p class="mt-1 text-sm text-slate-600">Order: {{ map.season_pivot?.order_index ?? 0 }}</p>
            </div>
            <UiBadge :variant="map.season_pivot?.is_active ? 'success' : 'neutral'">
              {{ map.season_pivot?.is_active ? 'active' : 'inactive' }}
            </UiBadge>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <UiButton variant="secondary" size="sm" @click="move(map, -1)">Move Up</UiButton>
            <UiButton variant="secondary" size="sm" @click="move(map, 1)">Move Down</UiButton>
            <UiButton variant="secondary" size="sm" @click="toggleMapActive(map)">Toggle Active</UiButton>
            <UiButton variant="danger" size="sm" @click="detach(map)">Remove</UiButton>
          </div>
        </UiCard>
      </div>
    </div>
  </main>
</template>
