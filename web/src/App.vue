<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import UiButton from '@/components/ui/UiButton.vue'
import { useAuth } from '@/composables/useAuth'

const auth = useAuth()
const router = useRouter()

const displayName = computed(() => {
  return auth.user.value?.discord_global_name
    || auth.user.value?.discord_username
    || auth.user.value?.name
    || 'Account'
})

async function handleLogout(): Promise<void> {
  await auth.logout()
  await router.replace('/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
      <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
        <div class="flex items-center gap-6">
          <RouterLink to="/seasons" class="text-base font-semibold text-slate-900">TM Club Seasons</RouterLink>
          <nav class="flex items-center gap-3 text-sm">
            <RouterLink to="/seasons" class="rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100">Seasons</RouterLink>
            <RouterLink v-if="auth.isAuthenticated.value" to="/dashboard" class="rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100">Dashboard</RouterLink>
            <RouterLink v-if="auth.isAdmin.value" to="/admin/seasons" class="rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100">Admin Seasons</RouterLink>
            <RouterLink v-if="auth.isAdmin.value" to="/admin/maps" class="rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100">Admin Maps</RouterLink>
          </nav>
        </div>

        <div class="flex items-center gap-2">
          <template v-if="auth.isAuthenticated.value">
            <span class="hidden text-sm text-slate-600 sm:inline">{{ displayName }}</span>
            <UiButton variant="secondary" size="sm" @click="handleLogout">Logout</UiButton>
          </template>
          <RouterLink
            v-else
            to="/login"
            class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700"
          >
            Login
          </RouterLink>
        </div>
      </div>
    </header>

    <RouterView />
  </div>
</template>
