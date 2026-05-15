<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { adminPrimaryClub, syncAdminPrimaryClub, type ApiTrackmaniaClub } from '@/lib/api'

const primaryClub = ref<ApiTrackmaniaClub | null>(null)
const clubIdInput = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const syncSummary = ref<null | { imported: number; updated: number; deactivated: number; enriched: number; total_members: number }>(null)

async function loadPrimaryClub(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    primaryClub.value = await adminPrimaryClub()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load primary club'
  } finally {
    loading.value = false
  }
}

async function handleSync(): Promise<void> {
  if (!clubIdInput.value.trim()) return

  loading.value = true
  error.value = null

  try {
    const result = await syncAdminPrimaryClub(clubIdInput.value.trim())
    syncSummary.value = {
      imported: result.imported,
      updated: result.updated,
      deactivated: result.deactivated,
      enriched: result.enriched,
      total_members: result.total_members,
    }
    primaryClub.value = result.club
    clubIdInput.value = ''
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Sync failed'
  } finally {
    loading.value = false
  }
}

onMounted(loadPrimaryClub)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">Primary Club</h1>
        <p class="mt-2 text-sm text-slate-600">Set or sync the primary Trackmania club by club ID.</p>

        <form class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="handleSync">
          <UiInput v-model="clubIdInput" placeholder="Trackmania Club ID" />
          <UiButton type="submit" :disabled="loading">Sync Club</UiButton>
        </form>

        <p v-if="syncSummary" class="mt-3 text-sm text-slate-700">
          Imported: {{ syncSummary.imported }} | Updated: {{ syncSummary.updated }} | Deactivated: {{ syncSummary.deactivated }} | Enriched: {{ syncSummary.enriched }} | Total: {{ syncSummary.total_members }}
        </p>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <div v-if="primaryClub" class="grid gap-3">
        <UiCard>
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">{{ primaryClub.name }}</h2>
              <p class="mt-1 text-sm text-slate-700"><strong>Club ID:</strong> {{ primaryClub.club_id }}</p>
              <p class="mt-1 text-sm text-slate-700"><strong>Members:</strong> {{ primaryClub.member_count ?? 'n/a' }}</p>
              <p class="mt-1 text-sm text-slate-700"><strong>Last Synced:</strong> {{ primaryClub.last_synced_at ?? 'never' }}</p>
            </div>
            <RouterLink to="/admin/clubs/members" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              View Members
            </RouterLink>
          </div>
        </UiCard>
      </div>
      <UiCard v-else>
        <p class="text-sm text-slate-700">No primary club set yet. Sync a club ID above to initialize it.</p>
      </UiCard>
    </div>
  </main>
</template>
