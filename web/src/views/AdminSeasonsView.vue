<script setup lang="ts">
import { onMounted, ref } from 'vue'

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

onMounted(loadSeasons)
</script>

<template>
  <main class="page">
    <h1>Admin Seasons</h1>
    <form class="create-form" @submit.prevent="handleCreate">
      <input v-model="name" placeholder="Season name" />
      <button type="submit" :disabled="loading">Create</button>
    </form>
    <p v-if="error" class="error">{{ error }}</p>

    <section class="list">
      <article v-for="season in seasons" :key="season.id" class="card">
        <h2>{{ season.name }} <small>({{ season.status }})</small></h2>
        <p>{{ season.description || 'No description yet' }}</p>
        <div class="row">
          <button type="button" @click="toggleActive(season)">{{ season.is_active ? 'Deactivate' : 'Activate' }}</button>
          <RouterLink :to="`/admin/seasons/${season.id}`">Edit Maps</RouterLink>
          <button type="button" @click="handleDelete(season.id)">Delete</button>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.page { padding: 1.5rem; }
.create-form { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.list { display: grid; gap: 1rem; }
.card { border: 1px solid #ddd; border-radius: 8px; padding: 0.75rem; }
.row { display: flex; gap: 0.75rem; align-items: center; }
.error { color: #b00020; }
</style>
