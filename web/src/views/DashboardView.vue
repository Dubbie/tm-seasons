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
    || 'Unknown user'
})

async function handleLogout(): Promise<void> {
  await auth.logout()
  await router.replace('/login')
}
</script>

<template>
  <main class="px-4 py-6 sm:px-6">
    <section class="mx-auto max-w-6xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
      <div class="mt-4 space-y-2 text-sm text-slate-700">
        <p><strong>User:</strong> {{ displayName }}</p>
        <p><strong>Discord ID:</strong> {{ auth.user.value?.discord_id ?? 'n/a' }}</p>
        <p><strong>Email:</strong> {{ auth.user.value?.email ?? 'n/a' }}</p>
        <p><strong>Admin:</strong> {{ auth.isAdmin.value ? 'yes' : 'no' }}</p>
      </div>
      <UiButton class="mt-6" @click="handleLogout">
        Logout
      </UiButton>
    </section>
  </main>
</template>
