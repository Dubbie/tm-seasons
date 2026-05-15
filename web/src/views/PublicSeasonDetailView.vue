<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import {
  publicSeason,
  publicSeasonLeaderboard,
  type ApiSeason,
  type ApiSeasonLeaderboardEntry,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const leaderboard = ref<{ map: { id: number; name: string | null; uid: string }; entries: ApiSeasonLeaderboardEntry[] }[]>([])
const activeMapIndex = ref(0)
const loading = ref(false)

const activeEntries = computed(() => leaderboard.value[activeMapIndex.value]?.entries ?? [])
const activeMap = computed(() => leaderboard.value[activeMapIndex.value]?.map)
const lbError = ref<string | null>(null)

async function loadSeason(): Promise<void> {
  loading.value = true
  lbError.value = null
  try {
    const slug = String(route.params.slug)
    const [seasonData, lbData] = await Promise.all([
      publicSeason(slug),
      publicSeasonLeaderboard(slug),
    ])
    season.value = seasonData
    leaderboard.value = lbData.leaderboard
  } catch (err) {
    lbError.value = err instanceof Error ? err.message : 'Failed to load season data'
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

onMounted(loadSeason)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Season Detail</h1>
        <p v-if="loading" class="mt-2 text-sm text-slate-500">Loading...</p>
        <template v-else-if="season">
          <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ season.name }}</h2>
          <p class="mt-1 text-sm text-slate-600">{{ season.description || 'No description yet' }}</p>
        </template>
      </UiCard>

      <UiCard v-if="season">
        <h3 class="text-lg font-semibold text-slate-900">Maps</h3>
        <ul class="mt-3 space-y-2">
          <li v-for="map in season.maps || []" :key="map.id" class="flex flex-wrap items-center gap-2 text-sm text-slate-700">
            <span>{{ map.season_pivot?.order_index ?? 0 }} - {{ map.name || map.uid }}</span>
            <UiBadge v-if="map.season_pivot?.is_active === false" variant="neutral">inactive</UiBadge>
          </li>
        </ul>
      </UiCard>

      <p v-if="lbError" class="text-sm text-red-700">{{ lbError }}</p>

      <template v-if="leaderboard.length">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(item, idx) in leaderboard"
            :key="idx"
            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"
            :class="idx === activeMapIndex ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="activeMapIndex = idx"
          >
            {{ item.map.name || `Map ${idx + 1}` }}
          </button>
        </div>

        <UiCard v-if="activeEntries.length">
          <h3 class="text-lg font-semibold text-slate-900">{{ activeMap?.name ?? 'Unknown Map' }}</h3>
          <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead>
                <tr class="border-b text-slate-500">
                  <th class="pb-2 pr-4 font-medium">#</th>
                  <th class="pb-2 pr-4 font-medium">Player</th>
                  <th class="pb-2 font-medium">PB</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(entry, index) in activeEntries" :key="entry.id" class="border-b last:border-b-0">
                  <td class="py-2 pr-4 text-slate-700">{{ index + 1 }}</td>
                  <td class="py-2 pr-4 font-medium text-slate-900">{{ entry.player?.display_name ?? 'Unknown' }}</td>
                  <td class="py-2 text-slate-700">{{ formatScore(entry.time_ms) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </UiCard>

        <p v-else class="text-sm text-slate-500">No leaderboard data for this map yet.</p>
      </template>
    </div>
  </main>
</template>
