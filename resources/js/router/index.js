import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    component: () => import('@/views/LayoutView.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
      },
      {
        path: 'caisse',
        name: 'caisse',
        component: () => import('@/views/CaisseView.vue'),
      },
      {
        path: 'inventaire',
        name: 'inventaire',
        component: () => import('@/views/InventaireView.vue'),
        meta: { adminOnly: true },
      },
      {
        path: 'produits',
        name: 'produits',
        component: () => import('@/views/ProduitsView.vue'),
        meta: { adminOnly: true },
      },
      {
        path: 'ventes',
        name: 'ventes',
        component: () => import('@/views/VentesView.vue'),
        meta: { adminOnly: true },
      },
      {
        path: 'configuration',
        name: 'configuration',
        component: () => import('@/views/ConfigurationView.vue'),
        meta: { adminOnly: true },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.public) {
    return next()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login' })
  }

  if (to.meta.adminOnly && !authStore.isAdmin) {
    return next({ name: 'caisse' })
  }

  next()
})

export default router
