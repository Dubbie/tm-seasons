<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import {
  adminSeason,
  adminSeasonEvents,
  adminSeasonPoints,
  recalculateSeasonPoints,
  type ApiPointEvent,
  type ApiSeason,
  type ApiStandingEntry,
} from '@/lib/api'
import { describeEvent } from '@/lib/eventDescriptions'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const events = ref<ApiPointEvent[]>([])
const standings = ref<ApiStandingEntry[]>([])
const loading = ref(false)
const recalculating = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const currentPage = ref(1)
const lastPage = ref(1)
const selectedType = ref<string>('')

const seasonId = computed(() => Number(route.params.id))

const eventTypes = [
  { value: '', label: 'All types' },
  { value: 'first_finish', label: 'First Finish' },
  { value: 'medal_bronze', label: 'Bronze Medal' },
  { value: 'medal_silver', label: 'Silver Medal' },
  { value: 'medal_gold', label: 'Gold Medal' },
  { value: 'medal_author', label: 'Author Medal' },
  { value: 'entered_top_20', label: 'Entered Top 20' },
  { value: 'entered_top_10', label: 'Entered Top 10' },
  { value: 'entered_top_5', label: 'Entered Top 5' },
  { value: 'entered_top_1', label: 'Entered Top 1' },
]

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, eventsData, standingsData] = await Promise.all([
      adminSeason(seasonId.value),
      adminSeasonEvents(seasonId.value, {
        page: currentPage.value,
        type: selectedType.value || undefined,
        per_page: 50,
      }),
      adminSeasonPoints(seasonId.value),
    ])
    season.value = seasonData
    events.value = eventsData.data
    standings.value = standingsData.standings
    if (eventsData.meta) {
      lastPage.value = eventsData.meta.last_page
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load data'
  } finally {
    loading.value = false
  }
}

async function handleRecalculate(): Promise<void> {
  if (!confirm('This will wipe all existing events and milestones. Are you sure?')) {
    return
  }

  recalculating.value = true
  error.value = null
  success.value = null

  try {
    const result = await recalculateSeasonPoints(seasonId.value)
    success.value = `Recalculation complete: ${result.events_count} events created.`
    await loadData()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Recalculation failed'
  } finally {
    recalculating.value = false
  }
}

function handleTypeFilter(): void {
  currentPage.value = 1
  loadData()
}

function nextPage(): void {
  if (currentPage.value < lastPage.value) {
    currentPage.value++
    loadData()
  }
}

function prevPage(): void {
  if (currentPage.value > 1) {
    currentPage.value--
    loadData()
  }
}

function eventBadgeVariant(type: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
  if (type === 'first_finish') return 'success'
  if (type.startsWith('medal')) return 'info'
  if (type.startsWith('entered_top')) return 'warning'
  return 'neutral'
}

onMounted(loadData)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Admin Season Events</h1>
        <p v-if="season" class="mt-1 text-sm text-slate-600">{{ season.name }}</p>

        <div class="mt-4 flex items-center gap-3">
          <UiButton
            variant="danger"
            :disabled="recalculating"
            @click="handleRecalculate"
          >
            {{ recalculating ? 'Recalculating...' : 'Recalculate Events' }}
          </UiButton>
        </div>

        <p v-if="success" class="mt-3 text-sm text-green-700">{{ success }}</p>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
      </UiCard>

      <UiCard v-if="standings.length">
        <h2 class="text-lg font-semibold text-slate-900">Standings</h2>
        <div class="mt-3 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b text-slate-500">
                <th class="pb-2 pr-3 font-medium">#</th>
                <th class="pb-2 pr-3 font-medium">Player</th>
                <th class="pb-2 pr-3 font-medium">Points</th>
                <th class="pb-2 font-medium">Maps</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in standings" :key="entry.player_id" class="border-b last:border-b-0">
                <td class="py-2 pr-3 font-bold text-slate-700">{{ entry.position }}</td>
                <td class="py-2 pr-3 font-medium text-slate-900">{{ entry.player?.display_name ?? 'Unknown' }}</td>
                <td class="py-2 pr-3 font-semibold text-blue-700">{{ entry.total_points }}</td>
                <td class="py-2 text-slate-700">{{ entry.maps_completed }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>

      <UiCard>
        <h2 class="text-lg font-semibold text-slate-900">Events</h2>

        <div class="mt-3 flex items-end gap-3">
          <UiSelect
            id="event-type-filter"
            v-model="selectedType"
            label="Filter by Type"
            class="max-w-xs"
            @update:model-value="handleTypeFilter"
          >
            <option
              v-for="option in eventTypes"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </UiSelect>
        </div>

        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>

        <div v-if="events.length" class="mt-3 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b text-slate-500">
                <th class="pb-2 pr-3 font-medium">Points</th>
                <th class="pb-2 pr-3 font-medium">Type</th>
                <th class="pb-2 pr-3 font-medium">Player</th>
                <th class="pb-2 pr-3 font-medium">Description</th>
                <th class="pb-2 font-medium">Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="event in events" :key="event.id" class="border-b last:border-b-0">
                <td class="py-2 pr-3 font-semibold text-blue-700">{{ event.points > 0 ? '+' : '' }}{{ event.points }}</td>
                <td class="py-2 pr-3">
                  <UiBadge :variant="eventBadgeVariant(event.type)">{{ event.type }}</UiBadge>
                </td>
                <td class="py-2 pr-3 text-slate-700">{{ event.player?.display_name ?? 'Unknown' }}</td>
                <td class="py-2 pr-3 text-slate-600 max-w-md truncate">{{ describeEvent(event) }}</td>
                <td class="py-2 whitespace-nowrap text-slate-500">{{ event.created_at ? new Date(event.created_at).toLocaleString() : '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-else-if="!loading" class="mt-3 text-sm text-slate-500">No events found.</p>

        <div v-if="events.length" class="mt-4 flex items-center justify-center gap-3">
          <button
            :disabled="currentPage <= 1"
            class="rounded-md border px-3 py-1.5 text-sm font-medium disabled:opacity-50"
            @click="prevPage"
          >
            Previous
          </button>
          <span class="text-sm text-slate-600">Page {{ currentPage }} of {{ lastPage }}</span>
          <button
            :disabled="currentPage >= lastPage"
            class="rounded-md border px-3 py-1.5 text-sm font-medium disabled:opacity-50"
            @click="nextPage"
          >
            Next
          </button>
        </div>
      </UiCard>
    </div>
  </main>
</template>
