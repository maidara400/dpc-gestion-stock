<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Mobile top bar -->
    <header class="lg:hidden fixed top-0 inset-x-0 z-30 bg-white border-b border-gray-100 h-14 flex items-center px-4 gap-3">
      <button @click="sidebarOpen = true" class="p-2 -ml-1 rounded-lg text-gray-500 hover:bg-gray-100">
        <Bars3Icon class="w-6 h-6" />
      </button>
      <div class="flex items-center gap-2 flex-1 min-w-0">
        <div class="w-7 h-7 bg-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <span class="font-semibold text-gray-900 text-sm truncate">DPC Padel Stock</span>
      </div>
      <div v-if="alertCount > 0" class="flex-shrink-0">
        <span class="badge-alert">{{ alertCount }}</span>
      </div>
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0"
        :class="authStore.isAdmin ? 'bg-green-600' : 'bg-blue-600'">
        {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
      </div>
    </header>

    <!-- Backdrop -->
    <transition name="backdrop">
      <div v-if="sidebarOpen" @click="sidebarOpen = false"
        class="lg:hidden fixed inset-0 z-40 bg-black/50 backdrop-blur-sm" />
    </transition>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col
             transition-transform duration-300 ease-in-out lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
    >
      <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-100">
        <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm">DPC Padel</p>
          <p class="text-xs text-gray-500">Gestion Stock</p>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 rounded text-gray-400 hover:text-gray-600">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <template v-if="authStore.isAdmin">
          <router-link
            v-for="item in adminNav" :key="item.label" :to="item.to"
            @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="isActive(item.to) ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            {{ item.label }}
            <span v-if="item.badge" class="ml-auto bg-red-100 text-red-700 text-xs font-medium px-1.5 py-0.5 rounded-full">
              {{ item.badge }}
            </span>
          </router-link>
        </template>
        <template v-else>
          <router-link :to="{ name: 'caisse' }" @click="sidebarOpen = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="isActive({ name: 'caisse' }) ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50'"
          >
            <ShoppingCartIcon class="w-5 h-5" />
            Caisse
          </router-link>
        </template>
      </nav>

      <div class="p-4 border-t border-gray-100">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0"
            :class="authStore.isAdmin ? 'bg-green-600' : 'bg-blue-600'">
            {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500 capitalize">{{ authStore.user?.role }}</p>
          </div>
        </div>
        <button @click="handleLogout" class="btn-secondary w-full justify-center text-xs py-2">
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="lg:ml-64 min-h-screen pt-14 lg:pt-0">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 lg:py-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  HomeIcon, ShoppingCartIcon, ArchiveBoxIcon, CubeIcon,
  ChartBarIcon, Cog6ToothIcon, Bars3Icon, XMarkIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const alertCount = ref(0)
const sidebarOpen = ref(false)

onMounted(async () => {
  if (authStore.isAdmin) {
    try {
      const { data } = await axios.get('/dashboard')
      alertCount.value = data.stats.alert_count
    } catch {}
  }
})

const adminNav = computed(() => [
  { label: 'Tableau de bord', to: { name: 'dashboard' }, icon: HomeIcon },
  { label: 'Caisse', to: { name: 'caisse' }, icon: ShoppingCartIcon },
  { label: 'Inventaire', to: { name: 'inventaire' }, icon: ArchiveBoxIcon },
  { label: 'Produits', to: { name: 'produits' }, icon: CubeIcon },
  { label: 'Historique ventes', to: { name: 'ventes' }, icon: ChartBarIcon },
  { label: 'Configuration', to: { name: 'configuration' }, icon: Cog6ToothIcon, badge: alertCount.value > 0 ? alertCount.value : null },
])

function isActive(to) {
  if (to.name) return route.name === to.name
  return route.path === to
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.backdrop-enter-active, .backdrop-leave-active { transition: opacity .2s ease; }
.backdrop-enter-from, .backdrop-leave-to { opacity: 0; }
</style>
