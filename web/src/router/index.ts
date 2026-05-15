import { createRouter, createWebHistory } from 'vue-router'

import { useAuth } from '@/composables/useAuth'
import AdminMapsView from '@/views/AdminMapsView.vue'
import AdminSeasonEditView from '@/views/AdminSeasonEditView.vue'
import AdminSeasonsView from '@/views/AdminSeasonsView.vue'
import AdminView from '@/views/AdminView.vue'
import AuthCallbackView from '@/views/AuthCallbackView.vue'
import DashboardView from '@/views/DashboardView.vue'
import LoginView from '@/views/LoginView.vue'
import PublicSeasonDetailView from '@/views/PublicSeasonDetailView.vue'
import PublicSeasonsView from '@/views/PublicSeasonsView.vue'

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
    {
      path: '/admin/maps',
      component: AdminMapsView,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
      },
    },
    {
      path: '/admin/seasons',
      component: AdminSeasonsView,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
      },
    },
    {
      path: '/admin/seasons/:id',
      component: AdminSeasonEditView,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
      },
    },
    {
      path: '/seasons',
      component: PublicSeasonsView,
    },
    {
      path: '/seasons/:slug',
      component: PublicSeasonDetailView,
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
