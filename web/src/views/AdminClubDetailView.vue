<script setup lang="ts">
import { onMounted, ref } from 'vue'

import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiCard from '@/components/ui/UiCard.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import {
  adminPrimaryClub,
  adminPrimaryClubMembers,
  syncAdminPrimaryClub,
  type ApiClubMember,
  type ApiTrackmaniaClub,
} from '@/lib/api'

const club = ref<ApiTrackmaniaClub | null>(null)
const members = ref<ApiClubMember[]>([])
const search = ref('')
const active = ref<'1' | '0' | ''>('1')
const sort = ref<'display_name' | 'last_seen'>('display_name')
const direction = ref<'asc' | 'desc'>('asc')
const loading = ref(false)
const error = ref<string | null>(null)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

async function loadClub(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    club.value = await adminPrimaryClub()
    await loadMembers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load club'
  } finally {
    loading.value = false
  }
}

async function loadMembers(page = currentPage.value): Promise<void> {
  const response = await adminPrimaryClubMembers({
    search: search.value || undefined,
    active: active.value,
    sort: sort.value,
    direction: direction.value,
    page,
  })

  members.value = response.data
  currentPage.value = response.meta?.current_page ?? 1
  lastPage.value = response.meta?.last_page ?? 1
  total.value = response.meta?.total ?? response.data.length
}

async function applyFilters(): Promise<void> {
  await loadMembers(1)
}

async function goToPreviousPage(): Promise<void> {
  if (currentPage.value <= 1) return
  await loadMembers(currentPage.value - 1)
}

async function goToNextPage(): Promise<void> {
  if (currentPage.value >= lastPage.value) return
  await loadMembers(currentPage.value + 1)
}

async function handleSync(): Promise<void> {
  if (!club.value?.club_id) return

  loading.value = true
  error.value = null

  try {
    await syncAdminPrimaryClub(club.value.club_id)
    await loadClub()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Sync failed'
  } finally {
    loading.value = false
  }
}

onMounted(loadClub)
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <div class="mx-auto max-w-6xl space-y-4">
      <UiCard>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ club?.name ?? 'Club' }}</h1>
            <p class="mt-1 text-sm text-slate-700">Club ID: {{ club?.club_id ?? 'n/a' }}</p>
          </div>
          <UiButton :disabled="loading" @click="handleSync">Sync Members</UiButton>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-4">
          <UiInput v-model="search" placeholder="Search display name" />
          <UiSelect v-model="active">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
            <option value="">All</option>
          </UiSelect>
          <UiSelect v-model="sort">
            <option value="display_name">Display Name</option>
            <option value="last_seen">Last Seen</option>
          </UiSelect>
          <UiSelect v-model="direction">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </UiSelect>
        </div>

        <div class="mt-3">
          <UiButton size="sm" :disabled="loading" @click="applyFilters">Apply Filters</UiButton>
        </div>

        <p v-if="error" class="mt-3 text-sm text-red-700">{{ error }}</p>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">Loading...</p>
      </UiCard>

      <UiCard v-for="member in members" :key="member.id">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ member.player.display_name }}</h2>
            <p class="mt-1 text-sm text-slate-700">
              <strong>Account ID:</strong>
              {{ member.player.account_id ?? member.player.account_id ?? 'n/a' }}
            </p>
            <p class="mt-1 text-sm text-slate-700">
              <strong>Zone:</strong>
              {{ member.player.zone_name ?? member.player.zone_id ?? 'n/a' }}
            </p>
            <p class="mt-1 text-sm text-slate-700">
              <strong>Last Seen:</strong> {{ member.player.last_seen_in_club_at ?? 'n/a' }}
            </p>
          </div>
          <UiBadge :variant="member.is_active ? 'success' : 'neutral'">{{
            member.is_active ? 'Active' : 'Inactive'
          }}</UiBadge>
        </div>
      </UiCard>

      <UiCard>
        <div class="flex items-center justify-between gap-3">
          <p class="text-sm text-slate-700">
            Showing page {{ currentPage }} of {{ lastPage }} ({{ total }} members)
          </p>
          <div class="flex items-center gap-2">
            <UiButton
              size="sm"
              variant="secondary"
              :disabled="loading || currentPage <= 1"
              @click="goToPreviousPage"
            >
              Previous
            </UiButton>
            <UiButton
              size="sm"
              variant="secondary"
              :disabled="loading || currentPage >= lastPage"
              @click="goToNextPage"
            >
              Next
            </UiButton>
          </div>
        </div>
      </UiCard>
    </div>
  </main>
</template>
