<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
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
        <p class="mt-4 text-sm text-slate-500">Leaderboard placeholders coming in a later milestone.</p>
      </UiCard>
    </div>
  </main>
</template>
