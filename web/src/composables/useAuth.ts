import { computed, ref } from 'vue'

import { fetchMe, logout as apiLogout, type ApiUser } from '@/lib/api'

const user = ref<ApiUser | null>(null)
const loading = ref(false)
const initialized = ref(false)

export function useAuth() {
  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.is_admin === true)

  async function fetchUser(): Promise<ApiUser | null> {
    loading.value = true

    try {
      const me = await fetchMe()
      user.value = me
      initialized.value = true
      return me
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    loading.value = true

    try {
      await apiLogout()
      user.value = null
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    loading,
    initialized,
    isAuthenticated,
    isAdmin,
    fetchUser,
    logout,
  }
}
