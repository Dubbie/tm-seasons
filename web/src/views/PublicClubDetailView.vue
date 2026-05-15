<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiCard from '@/components/ui/UiCard.vue'
import { publicClub, publicClubMembers, type ApiClubMember, type ApiTrackmaniaClub } from '@/lib/api'

const route = useRoute()
const club = ref<ApiTrackmaniaClub | null>(null)
const members = ref<ApiClubMember[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

async function load(): Promise<void> {
  const id = Number(route.params.id)
  if (!id) return

  loading.value = true
  error.value = null

  try {
    club.value = await publicClub(id)
    members.value = await publicClubMembers(id, '1')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load club'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <h1 class="text-2xl font-semibold text-slate-900">{{ club?.name ?? 'Club' }}</h1>
        <p class="mt-1 text-sm text-slate-700">Club ID: {{ club?.club_id ?? 'n/a' }}</p>
        <p class="mt-1 text-sm text-slate-700">Members: {{ club?.member_count ?? members.length }}</p>
        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <UiCard v-for="member in members" :key="member.id">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ member.player.display_name }}</h2>
            <p class="mt-1 text-sm text-slate-700">{{ member.player.zone_name ?? member.player.zone_id ?? 'n/a' }}</p>
          </div>
          <UiBadge variant="success">Active</UiBadge>
        </div>
      </UiCard>
    </div>
  </main>
</template>
