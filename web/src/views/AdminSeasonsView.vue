<script setup lang="ts">
import { onMounted, ref } from 'vue'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { adminSeasons, createAdminSeason, deleteAdminSeason, updateAdminSeason, type ApiSeason } from '@/lib/api'

const seasons = ref<ApiSeason[]>([])
const name = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

async function loadSeasons(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    seasons.value = await adminSeasons()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load seasons'
  } finally {
    loading.value = false
  }
}

async function handleCreate(): Promise<void> {
  if (!name.value.trim()) return

  loading.value = true
  error.value = null

  try {
    const season = await createAdminSeason({ name: name.value.trim() })
    seasons.value = [season, ...seasons.value]
    name.value = ''
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Create failed'
  } finally {
    loading.value = false
  }
}

async function toggleActive(season: ApiSeason): Promise<void> {
  try {
    await updateAdminSeason(season.id, { is_active: !season.is_active })
    await loadSeasons()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Toggle failed'
  }
}

async function handleDelete(id: number): Promise<void> {
  try {
    await deleteAdminSeason(id)
    seasons.value = seasons.value.filter((entry) => entry.id !== id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Delete failed'
  }
}

function statusVariant(status: string): 'neutral' | 'success' | 'warning' {
  if (status === 'active') return 'success'
  if (status === 'upcoming') return 'warning'
  return 'neutral'
}

onMounted(loadSeasons)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Admin Seasons</h1>
        <form class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="handleCreate">
          <UiInput v-model="name" placeholder="Season name" />
          <UiButton type="submit" :disabled="loading">Create</UiButton>
        </form>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
      </UiCard>

      <div class="grid gap-3">
        <UiCard v-for="season in seasons" :key="season.id">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ season.name }}</h2>
            <UiBadge :variant="statusVariant(season.status)">{{ season.status }}</UiBadge>
          </div>
          <p class="mt-1 text-sm text-slate-600">{{ season.description || 'No description yet' }}</p>
          <div class="mt-4 flex flex-wrap items-center gap-2">
            <UiButton variant="secondary" size="sm" @click="toggleActive(season)">{{ season.is_active ? 'Deactivate' : 'Activate' }}</UiButton>
            <RouterLink :to="`/admin/seasons/${season.id}`" custom v-slot="{ navigate }">
              <UiButton variant="secondary" size="sm" @click="navigate">Edit Maps</UiButton>
            </RouterLink>
            <RouterLink :to="`/admin/seasons/${season.id}/leaderboard`" custom v-slot="{ navigate }">
              <UiButton variant="secondary" size="sm" @click="navigate">Leaderboard</UiButton>
            </RouterLink>
            <UiButton variant="danger" size="sm" @click="handleDelete(season.id)">Delete</UiButton>
          </div>
        </UiCard>
      </div>
    </div>
  </main>
</template>
