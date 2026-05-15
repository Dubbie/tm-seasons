<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

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
  <main class="dashboard-layout">
    <section class="card">
      <h1>Dashboard</h1>
      <p><strong>User:</strong> {{ displayName }}</p>
      <p><strong>Discord ID:</strong> {{ auth.user.value?.discord_id ?? 'n/a' }}</p>
      <p><strong>Email:</strong> {{ auth.user.value?.email ?? 'n/a' }}</p>
      <p><strong>Admin:</strong> {{ auth.isAdmin.value ? 'yes' : 'no' }}</p>
      <button type="button" class="logout-btn" @click="handleLogout">Logout</button>
    </section>
  </main>
</template>

<style scoped>
.dashboard-layout {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(180deg, #f7fbff 0%, #eef4fb 100%);
}

.card {
  max-width: 680px;
  margin: 0 auto;
  padding: 1.5rem;
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #d8e5f2;
}

.logout-btn {
  margin-top: 1rem;
  padding: 0.65rem 1rem;
  border: 0;
  border-radius: 8px;
  background: #254f91;
  color: white;
  cursor: pointer;
}
</style>
