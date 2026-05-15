<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import {
  publicSeason,
  publicSeasonStandings,
  type ApiSeason,
  type ApiStandingEntry,
} from '@/lib/api'

const route = useRoute()
const season = ref<ApiSeason | null>(null)
const standings = ref<ApiStandingEntry[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

const slug = computed(() => String(route.params.slug))

async function loadData(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const [seasonData, standingsData] = await Promise.all([
      publicSeason(slug.value),
      publicSeasonStandings(slug.value),
    ])
    season.value = seasonData
    standings.value = standingsData.standings
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load standings'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Season Standings</h1>
            <p v-if="season" class="mt-1 text-sm text-slate-600">{{ season.name }}</p>
          </div>
          <div v-if="season" class="flex gap-2">
            <RouterLink
              :to="`/seasons/${season.slug}/events`"
              class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
            >
              Event Feed
            </RouterLink>
          </div>
        </div>
      </UiCard>

      <p v-if="loading" class="text-sm text-slate-500">Loading...</p>
      <p v-if="error" class="text-sm text-red-700">{{ error }}</p>

      <UiCard v-if="standings.length">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b text-slate-500">
                <th class="pb-2 pr-4 font-medium">#</th>
                <th class="pb-2 pr-4 font-medium">Player</th>
                <th class="pb-2 pr-4 font-medium">Total Points</th>
                <th class="pb-2 pr-4 font-medium">Maps Completed</th>
                <th class="pb-2 pr-4 font-medium">Events</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in standings" :key="entry.player_id" class="border-b last:border-b-0">
                <td class="py-2 pr-4 font-bold text-slate-700">{{ entry.position }}</td>
                <td class="py-2 pr-4 font-medium text-slate-900">
                  <RouterLink
                    v-if="entry.player"
                    :to="`/seasons/${slug}/players/${entry.player_id}`"
                    class="text-blue-600 hover:text-blue-800"
                  >
                    {{ entry.player.display_name }}
                  </RouterLink>
                  <span v-else>Unknown</span>
                </td>
                <td class="py-2 pr-4 font-semibold text-blue-700">{{ entry.total_points }}</td>
                <td class="py-2 pr-4 text-slate-700">{{ entry.maps_completed }}</td>
                <td class="py-2 text-slate-700">{{ entry.event_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>

      <p v-else-if="!loading" class="text-sm text-slate-500">No standings data yet.</p>
    </div>
  </main>
</template>
