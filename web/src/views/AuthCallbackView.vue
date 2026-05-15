<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuth } from '@/composables/useAuth'

const auth = useAuth()
const router = useRouter()
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const me = await auth.fetchUser()

    if (!me) {
      await router.replace('/login')
      return
    }

    await router.replace('/dashboard')
  } catch {
    error.value = 'Could not complete login. Please try again.'
  }
})
</script>

<template>
  <main class="callback-page">
    <p v-if="!error">Completing sign-in...</p>
    <p v-else>{{ error }}</p>
  </main>
</template>

<style scoped>
.callback-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  background: #f6fafc;
  color: #1b2b3a;
}
</style>
