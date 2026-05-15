<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import { displayTrackmaniaMapName } from '@/lib/trackmaniaText'
import {
  adminMaps,
  adminSeason,
  attachMapToSeason,
  finalizeAdminSeason,
  removeSeasonMap,
  updateAdminSeason,
  updateSeasonMap,
  type ApiMap,
  type ApiSeason,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const availableMaps = ref<ApiMap[]>([])
const selectedMapId = ref<number | null>(null)
const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const info = ref<string | null>(null)

const form = ref({
  starts_at: '',
  ends_at: '',
  status: 'draft' as ApiSeason['status'],
})

const seasonId = computed(() => Number(route.params.id))

function toInputDate(value: string | null): string {
  if (!value) return ''
  const d = new Date(value)
  const offset = d.getTimezoneOffset() * 60 * 1000
  const local = new Date(d.getTime() - offset)
  return local.toISOString().slice(0, 16)
}

function toIsoOrNull(value: string): string | null {
  if (!value) return null
  return new Date(value).toISOString()
}

function syncForm(): void {
  if (!season.value) return
  form.value = {
    starts_at: toInputDate(season.value.starts_at),
    ends_at: toInputDate(season.value.ends_at),
    status: season.value.status,
  }
}

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, maps] = await Promise.all([adminSeason(seasonId.value), adminMaps()])
    season.value = seasonData
    availableMaps.value = maps
    syncForm()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load season'
  } finally {
    loading.value = false
  }
}

async function saveSeason(): Promise<void> {
  if (!season.value) return

  saving.value = true
  error.value = null
  info.value = null

  try {
    season.value = await updateAdminSeason(seasonId.value, {
      starts_at: toIsoOrNull(form.value.starts_at),
      ends_at: toIsoOrNull(form.value.ends_at),
      status: form.value.status,
    })
    syncForm()
    info.value = 'Season updated.'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Season update failed'
  } finally {
    saving.value = false
  }
}

async function finalizeSeason(): Promise<void> {
  if (!season.value) return

  saving.value = true
  error.value = null

  try {
    const result = await finalizeAdminSeason(seasonId.value)
    season.value = await adminSeason(seasonId.value)
    syncForm()
    info.value = `Finalized ${result.players_processed} players, granted ${result.rewards_granted.length} rewards.`
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Finalize failed'
  } finally {
    saving.value = false
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

        <div class="mt-4 grid gap-3 md:grid-cols-3">
          <UiInput v-model="form.starts_at" type="datetime-local" placeholder="Start" />
          <UiInput v-model="form.ends_at" type="datetime-local" placeholder="End" />
          <UiSelect v-model="form.status" label="Status">
            <option value="draft">draft</option>
            <option value="scheduled">scheduled</option>
            <option value="active">active</option>
            <option value="ended">ended</option>
            <option value="finalized">finalized</option>
          </UiSelect>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <UiButton :disabled="saving" @click="saveSeason">Save Season</UiButton>
          <UiButton v-if="season?.status === 'ended'" variant="secondary" :disabled="saving" @click="finalizeSeason">Finalize Season</UiButton>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
          <UiSelect
            id="season-map-select"
            v-model="selectedMapId"
            label="Attach imported map"
            class="max-w-xl"
          >
            <option :value="''">Select imported map</option>
            <option v-for="map in availableMaps" :key="map.id" :value="String(map.id)">{{ displayTrackmaniaMapName(map.name, map.uid) }}</option>
          </UiSelect>
          <UiButton variant="secondary" @click="handleAttach">Attach Map</UiButton>
        </div>

        <p v-if="info" class="mt-3 text-sm text-green-700">{{ info }}</p>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <div v-if="season?.maps?.length" class="grid gap-3">
        <UiCard v-for="map in season.maps" :key="map.id">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">{{ displayTrackmaniaMapName(map.name, map.uid) }}</h2>
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
