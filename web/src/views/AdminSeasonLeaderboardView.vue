<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import {
  adminPolls,
  adminSeason,
  adminSeasonRecords,
  pollSeason,
  type ApiLeaderboardPoll,
  type ApiMap,
  type ApiSeason,
  type ApiSeasonMapPlayerRecord,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const polls = ref<ApiLeaderboardPoll[]>([])
const records = ref<ApiSeasonMapPlayerRecord[]>([])
const maps = ref<ApiMap[]>([])
const selectedMapId = ref<string>('')
const loading = ref(false)
const polling = ref(false)
const pollResult = ref<string | null>(null)
const error = ref<string | null>(null)

const seasonId = computed(() => Number(route.params.id))
const lastPoll = computed(() => polls.value[0] ?? null)

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, pollsData, recordsData] = await Promise.all([
      adminSeason(seasonId.value),
      adminPolls(),
      adminSeasonRecords(seasonId.value, {
        map_id: selectedMapId.value ? Number(selectedMapId.value) : undefined,
      }),
    ])
    season.value = seasonData
    maps.value = seasonData.maps ?? []
    polls.value = pollsData
    records.value = recordsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load data'
  } finally {
    loading.value = false
  }
}

async function triggerPoll(): Promise<void> {
  polling.value = true
  pollResult.value = null
  error.value = null

  try {
    const result = await pollSeason(seasonId.value)
    pollResult.value = `Completed: ${result.maps_processed} maps, ${result.snapshots_created} snapshots, ${result.improvements_detected} improvements`
    await loadData()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Poll failed'
  } finally {
    polling.value = false
  }
}

async function handleMapFilter(): Promise<void> {
  await loadData()
}

function pollStatusVariant(status: string): 'neutral' | 'success' | 'warning' | 'danger' {
  if (status === 'completed') return 'success'
  if (status === 'running') return 'warning'
  if (status === 'failed') return 'danger'
  return 'neutral'
}

function formatScore(ms: number | null): string {
  if (ms === null || ms < 0) return '-'
  const totalSeconds = Math.floor(ms / 1000)
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  const millis = ms % 1000
  return `${minutes}:${String(seconds).padStart(2, '0')}.${String(millis).padStart(3, '0')}`
}

onMounted(loadData)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Season Leaderboard</h1>
        <p v-if="season" class="mt-1 text-sm text-slate-600">{{ season.name }} ({{ season.slug }})</p>

        <div class="mt-4 flex items-center gap-3">
          <UiButton :disabled="polling" @click="triggerPoll">
            {{ polling ? 'Polling...' : 'Trigger Poll' }}
          </UiButton>
          <span v-if="polling" class="text-sm text-slate-500">Fetching leaderboard data...</span>
        </div>

        <div v-if="lastPoll" class="mt-3 flex flex-wrap gap-4 text-sm text-slate-600">
          <span>Last poll: <UiBadge :variant="pollStatusVariant(lastPoll.status)">{{ lastPoll.status }}</UiBadge></span>
          <span>{{ lastPoll.maps_polled_count }} maps / {{ lastPoll.players_processed_count }} snapshots</span>
          <span v-if="lastPoll.finished_at">{{ new Date(lastPoll.finished_at).toLocaleString() }}</span>
        </div>

        <p v-if="pollResult" class="mt-3 text-sm text-green-700">{{ pollResult }}</p>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
      </UiCard>

      <UiCard v-if="polls.length">
        <h2 class="text-lg font-semibold text-slate-900">Poll History</h2>
        <div class="mt-3 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b text-slate-500">
                <th class="pb-2 pr-4 font-medium">Status</th>
                <th class="pb-2 pr-4 font-medium">Maps</th>
                <th class="pb-2 pr-4 font-medium">Snapshots</th>
                <th class="pb-2 pr-4 font-medium">Started</th>
                <th class="pb-2 font-medium">Finished</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="poll in polls" :key="poll.id" class="border-b last:border-b-0">
                <td class="py-2 pr-4">
                  <UiBadge :variant="pollStatusVariant(poll.status)">{{ poll.status }}</UiBadge>
                </td>
                <td class="py-2 pr-4 text-slate-700">{{ poll.maps_polled_count }}</td>
                <td class="py-2 pr-4 text-slate-700">{{ poll.players_processed_count }}</td>
                <td class="py-2 pr-4 text-slate-700">{{ poll.started_at ? new Date(poll.started_at).toLocaleString() : '-' }}</td>
                <td class="py-2 text-slate-700">{{ poll.finished_at ? new Date(poll.finished_at).toLocaleString() : '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>

      <UiCard>
        <h2 class="text-lg font-semibold text-slate-900">Records</h2>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
          <UiSelect
            id="map-filter"
            v-model="selectedMapId"
            label="Filter by Map"
            class="max-w-xs"
            @update:model-value="handleMapFilter"
          >
            <option value="">All maps</option>
            <option v-for="map in maps" :key="map.id" :value="String(map.id)">{{ map.name || map.uid }}</option>
          </UiSelect>
          <p v-if="loading" class="text-sm text-slate-500">Loading...</p>
        </div>

        <div v-if="records.length" class="mt-3 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b text-slate-500">
                <th class="pb-2 pr-3 font-medium">#</th>
                <th class="pb-2 pr-3 font-medium">Player</th>
                <th class="pb-2 pr-3 font-medium">Map</th>
                <th class="pb-2 pr-3 font-medium">PB</th>
                <th class="pb-2 font-medium">Last Improved</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(record, index) in records" :key="record.id" class="border-b last:border-b-0">
                <td class="py-2 pr-3 text-slate-700">{{ index + 1 }}</td>
                <td class="py-2 pr-3 font-medium text-slate-900">{{ record.player?.display_name ?? 'Unknown' }}</td>
                <td class="py-2 pr-3 text-slate-700">{{ record.map?.name ?? '-' }}</td>
                <td class="py-2 pr-3 text-slate-700">{{ formatScore(record.time_ms) }}</td>
                <td class="py-2 text-slate-700">{{ record.last_improved_at ? new Date(record.last_improved_at).toLocaleString() : '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-else-if="!loading" class="mt-3 text-sm text-slate-500">No records found for this season.</p>
      </UiCard>
    </div>
  </main>
</template>
