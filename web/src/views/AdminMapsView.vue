<script setup lang="ts">
import { onMounted, ref } from 'vue'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { adminMaps, deleteAdminMap, importAdminMap, type ApiMap } from '@/lib/api'
import { displayTrackmaniaMapName } from '@/lib/trackmaniaText'

const maps = ref<ApiMap[]>([])
const uid = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

function formatTime(value: number | null): string {
  if (value === null) return 'n/a'
  return `${value} ms`
}

async function loadMaps(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    maps.value = await adminMaps()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load maps'
  } finally {
    loading.value = false
  }
}

async function handleImport(): Promise<void> {
  if (!uid.value.trim()) return

  loading.value = true
  error.value = null

  try {
    const imported = await importAdminMap(uid.value.trim())
    maps.value = [imported, ...maps.value.filter((entry) => entry.id !== imported.id)]
    uid.value = ''
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Import failed'
  } finally {
    loading.value = false
  }
}

async function handleDelete(id: number): Promise<void> {
  loading.value = true
  error.value = null

  try {
    await deleteAdminMap(id)
    maps.value = maps.value.filter((entry) => entry.id !== id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Delete failed'
  } finally {
    loading.value = false
  }
}

onMounted(loadMaps)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Admin Maps</h1>
        <form class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="handleImport">
          <UiInput v-model="uid" placeholder="Map UID" />
          <UiButton type="submit" :disabled="loading">Import</UiButton>
        </form>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <div class="grid gap-3">
        <UiCard v-for="map in maps" :key="map.id">
          <div class="grid gap-4 sm:grid-cols-[160px_1fr]">
            <img
              :src="map.thumbnail_url || 'https://placehold.co/160x90?text=No+Image'"
              alt="map thumbnail"
              class="h-[90px] w-[160px] rounded-md object-cover"
            />
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-900">{{ displayTrackmaniaMapName(map.name, map.uid) }}</h2>
                <UiBadge variant="info">{{ map.map_style || 'style n/a' }}</UiBadge>
                <UiBadge>{{ map.map_type || 'type n/a' }}</UiBadge>
              </div>
              <p class="mt-1 text-sm text-slate-700"><strong>Author:</strong> {{ map.author_name || map.author_account_id || 'n/a' }}</p>
              <p class="mt-1 text-sm text-slate-700">
                <strong>Medals:</strong>
                A {{ formatTime(map.author_time) }},
                G {{ formatTime(map.gold_time) }},
                S {{ formatTime(map.silver_time) }},
                B {{ formatTime(map.bronze_time) }}
              </p>
              <div class="mt-3">
                <UiButton variant="danger" size="sm" @click="handleDelete(map.id)">Delete</UiButton>
              </div>
            </div>
          </div>
        </UiCard>
      </div>
    </div>
  </main>
</template>
