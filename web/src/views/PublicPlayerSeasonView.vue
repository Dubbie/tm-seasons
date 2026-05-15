<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import {
  publicSeason,
  publicSeasonPlayer,
  type ApiPlayerSeasonDetail,
  type ApiSeason,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const playerData = ref<ApiPlayerSeasonDetail | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

const slug = computed(() => String(route.params.slug))
const playerId = computed(() => Number(route.params.player))

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, data] = await Promise.all([
      publicSeason(slug.value),
      publicSeasonPlayer(slug.value, playerId.value),
    ])
    season.value = seasonData
    playerData.value = data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load player data'
  } finally {
    loading.value = false
  }
}

function formatScore(ms: number | null): string {
  if (ms === null || ms < 0) return '-'
  const totalSeconds = Math.floor(ms / 1000)
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  const millis = ms % 1000
  return `${minutes}:${String(seconds).padStart(2, '0')}.${String(millis).padStart(3, '0')}`
}

function milestoneLabel(key: string): string {
  if (key === 'first_finish') return 'First Finish'
  if (key === 'medal_bronze') return 'Bronze Medal'
  if (key === 'medal_silver') return 'Silver Medal'
  if (key === 'medal_gold') return 'Gold Medal'
  if (key === 'medal_author') return 'Author Medal'
  if (key.startsWith('entered_top_')) {
    const pos = key.replace('entered_top_', '')
    return `Entered Club Top ${pos}`
  }
  return key
}

onMounted(loadData)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-4xl space-y-4">
      <UiCard>
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Player Season</h1>
            <p v-if="season" class="mt-1 text-sm text-slate-600">{{ season.name }}</p>
            <p v-if="playerData?.player" class="mt-1 text-lg font-medium text-slate-800">
              {{ playerData.player.display_name }}
            </p>
          </div>
          <div v-if="season" class="flex gap-2">
            <RouterLink
              :to="`/seasons/${season.slug}/standings`"
              class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
            >
              Standings
            </RouterLink>
            <RouterLink
              :to="`/seasons/${season.slug}/events`"
              class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
            >
              Events
            </RouterLink>
          </div>
        </div>
      </UiCard>

      <p v-if="loading" class="text-sm text-slate-500">Loading...</p>
      <p v-if="error" class="text-sm text-red-700">{{ error }}</p>

      <template v-if="playerData">
        <UiCard>
          <h2 class="text-lg font-semibold text-slate-900">Overview</h2>
          <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-3 text-center">
              <p class="text-2xl font-bold text-blue-700">{{ playerData.total_points }}</p>
              <p class="mt-1 text-xs text-slate-500">Total Points</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 text-center">
              <p class="text-2xl font-bold text-slate-700">{{ playerData.position ?? '-' }}</p>
              <p class="mt-1 text-xs text-slate-500">Club Rank</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 text-center">
              <p class="text-2xl font-bold text-slate-700">{{ playerData.maps_completed }}</p>
              <p class="mt-1 text-xs text-slate-500">Maps Completed</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 text-center">
              <p class="text-2xl font-bold text-slate-700">{{ playerData.event_count }}</p>
              <p class="mt-1 text-xs text-slate-500">Events</p>
            </div>
          </div>
        </UiCard>

        <UiCard v-if="playerData.milestones.length">
          <h2 class="text-lg font-semibold text-slate-900">Milestones</h2>
          <div class="mt-3 space-y-2">
            <div
              v-for="milestone in playerData.milestones"
              :key="milestone.id"
              class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2"
            >
              <div>
                <span class="text-sm font-medium text-slate-900">{{ milestoneLabel(milestone.milestone_key) }}</span>
                <span v-if="milestone.map" class="ml-2 text-sm text-slate-500">{{ milestone.map.name }}</span>
              </div>
              <span class="text-xs text-slate-400">{{ new Date(milestone.achieved_at).toLocaleString() }}</span>
            </div>
          </div>
        </UiCard>

        <UiCard v-if="playerData.events.length">
          <h2 class="text-lg font-semibold text-slate-900">Point History</h2>
          <div class="mt-3 space-y-2">
            <div
              v-for="event in playerData.events"
              :key="event.id"
              class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2"
            >
              <div class="flex items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
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
                  {{ event.description ?? event.type }}
                </span>
              </div>
              <span class="text-xs text-slate-400">{{ event.created_at ? new Date(event.created_at).toLocaleString() : '' }}</span>
            </div>
          </div>
        </UiCard>
      </template>
    </div>
  </main>
</template>
