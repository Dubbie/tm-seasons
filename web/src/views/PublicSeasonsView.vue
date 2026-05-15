<script setup lang="ts">
import { onMounted, ref } from 'vue'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import { publicSeasons, type ApiSeason } from '@/lib/api'

const seasons = ref<ApiSeason[]>([])
const loading = ref(false)

async function loadSeasons(): Promise<void> {
  loading.value = true
  try {
    seasons.value = await publicSeasons()
  } finally {
    loading.value = false
  }
}

function statusVariant(status: ApiSeason['status']): 'neutral' | 'success' | 'warning' {
  if (status === 'active') return 'success'
  if (status === 'scheduled') return 'warning'
  return 'neutral'
}

function label(status: ApiSeason['status']): string {
  if (status === 'scheduled') return 'Upcoming'
  if (status === 'active') return 'Active'
  if (status === 'ended') return 'Ended'
  if (status === 'finalized') return 'Finalized'
  return 'Draft'
}

onMounted(loadSeasons)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Seasons</h1>
        <p v-if="loading" class="mt-2 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <div class="grid gap-3">
        <UiCard v-for="season in seasons" :key="season.id">
          <div class="flex items-center gap-2">
            <RouterLink :to="`/seasons/${season.slug}`" class="text-lg font-semibold text-blue-700 hover:underline">
              {{ season.name }}
            </RouterLink>
            <UiBadge :variant="statusVariant(season.status)">{{ label(season.status) }}</UiBadge>
          </div>
          <p class="mt-1 text-sm text-slate-600">{{ season.description || 'No description yet' }}</p>
        </UiCard>
      </div>
    </div>
  </main>
</template>
