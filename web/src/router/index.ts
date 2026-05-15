import { createRouter, createWebHistory } from 'vue-router'

import { useAuth } from '@/composables/useAuth'
import AdminView from '@/views/AdminView.vue'
import AuthCallbackView from '@/views/AuthCallbackView.vue'
import DashboardView from '@/views/DashboardView.vue'
import LoginView from '@/views/LoginView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      component: LoginView,
    },
    {
      path: '/auth/callback',
      component: AuthCallbackView,
    },
    {
      path: '/dashboard',
      component: DashboardView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/admin',
      component: AdminView,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
      },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuth()

  if (!auth.initialized.value) {
    await auth.fetchUser()
  }

  const requiresAuth = to.meta.requiresAuth === true
  const requiresAdmin = to.meta.requiresAdmin === true

  if (requiresAuth && !auth.isAuthenticated.value) {
    return '/login'
  }

  if (requiresAdmin && !auth.isAdmin.value) {
    return '/dashboard'
  }

  if (to.path === '/login' && auth.isAuthenticated.value) {
    return '/dashboard'
  }

  return true
})

export default router
