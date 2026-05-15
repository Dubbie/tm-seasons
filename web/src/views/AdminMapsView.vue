<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminMaps, deleteAdminMap, importAdminMap, type ApiMap } from '@/lib/api'

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
  <main class="page">
    <h1>Admin Maps</h1>
    <form class="import-form" @submit.prevent="handleImport">
      <input v-model="uid" placeholder="Map UID" />
      <button type="submit" :disabled="loading">Import</button>
    </form>
    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <section class="list">
      <article v-for="map in maps" :key="map.id" class="card">
        <img :src="map.thumbnail_url || 'https://placehold.co/160x90?text=No+Image'" alt="map thumbnail" />
        <div>
          <h2>{{ map.name || map.uid }}</h2>
          <p><strong>Author:</strong> {{ map.author_name || map.author_account_id || 'n/a' }}</p>
          <p><strong>Style/Type:</strong> {{ map.map_style || 'n/a' }} / {{ map.map_type || 'n/a' }}</p>
          <p>
            <strong>Medals:</strong>
            A {{ formatTime(map.author_time) }},
            G {{ formatTime(map.gold_time) }},
            S {{ formatTime(map.silver_time) }},
            B {{ formatTime(map.bronze_time) }}
          </p>
          <button type="button" @click="handleDelete(map.id)">Delete</button>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.page { padding: 1.5rem; }
.import-form { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.list { display: grid; gap: 1rem; }
.card { display: grid; grid-template-columns: 160px 1fr; gap: 1rem; border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; }
img { width: 160px; height: 90px; object-fit: cover; border-radius: 6px; }
.error { color: #b00020; }
</style>
