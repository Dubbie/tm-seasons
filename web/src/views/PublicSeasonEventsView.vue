<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiCard from '@/components/ui/UiCard.vue'
import {
  publicSeason,
  publicSeasonEvents,
  type ApiPointEvent,
  type ApiSeason,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const events = ref<ApiPointEvent[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)
const lastPage = ref(1)

const slug = computed(() => String(route.params.slug))

function formatPoints(points: number): string {
  const sign = points >= 0 ? '+' : ''
  return `${sign}${points}`
}

function eventVariant(type: string): string {
  if (type === 'first_finish') return 'green'
  if (type.startsWith('medal')) return 'blue'
  if (type.startsWith('entered_top')) return 'purple'
  return 'neutral'
}

function playerLink(playerId: number): string {
  return `/seasons/${slug.value}/players/${playerId}`
}

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, eventsData] = await Promise.all([
      publicSeason(slug.value),
      publicSeasonEvents(slug.value, { page: currentPage.value }),
    ])
    season.value = seasonData
    events.value = eventsData.data
    if (eventsData.meta) {
      lastPage.value = eventsData.meta.last_page
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load events'
  } finally {
    loading.value = false
  }
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

onMounted(loadData)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-4xl space-y-4">
      <UiCard>
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Event Feed</h1>
            <p v-if="season" class="mt-1 text-sm text-slate-600">{{ season.name }}</p>
          </div>
          <div v-if="season" class="flex gap-2">
            <RouterLink
              :to="`/seasons/${season.slug}/standings`"
              class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
            >
              Standings
            </RouterLink>
          </div>
        </div>
      </UiCard>

      <p v-if="loading" class="text-sm text-slate-500">Loading...</p>
      <p v-if="error" class="text-sm text-red-700">{{ error }}</p>

      <div v-if="events.length" class="space-y-2">
        <div
          v-for="event in events"
          :key="event.id"
          class="rounded-lg border border-slate-200 bg-white px-4 py-3"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800': event.type === 'first_finish',
                  'bg-blue-100 text-blue-800': event.type.startsWith('medal'),
                  'bg-purple-100 text-purple-800': event.type.startsWith('entered_top'),
                  'bg-slate-100 text-slate-800': !event.type.startsWith('first_finish') && !event.type.startsWith('medal') && !event.type.startsWith('entered_top'),
                }"
              >
                {{ event.points > 0 ? '+' : '' }}{{ event.points }}
              </span>
              <span class="text-sm text-slate-700">
                <RouterLink
                  :to="playerLink(event.trackmania_player_id)"
                  class="font-medium text-blue-600 hover:text-blue-800"
                >
                  {{ event.player?.display_name ?? 'Unknown' }}
                </RouterLink>
                <span v-if="event.description" class="ml-1">{{ event.description }}</span>
              </span>
            </div>
            <span class="whitespace-nowrap text-xs text-slate-400">
              {{ event.created_at ? new Date(event.created_at).toLocaleString() : '' }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="events.length" class="flex items-center justify-center gap-3">
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

      <p v-else-if="!loading" class="text-sm text-slate-500">No events yet.</p>
    </div>
  </main>
</template>
