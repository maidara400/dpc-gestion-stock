<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Tableau de bord</h1>
      <button @click="loadData" class="btn-secondary text-xs">
        <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        <span class="hidden sm:inline">Actualiser</span>
      </button>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" v-if="stats">
      <div class="card p-4">
        <p class="text-xs text-gray-500">Produits actifs</p>
        <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ stats.total_products }}</p>
      </div>
      <div class="card p-4" :class="stats.alert_count > 0 ? 'border-red-200 bg-red-50' : ''">
        <p class="text-xs" :class="stats.alert_count > 0 ? 'text-red-600' : 'text-gray-500'">Alertes stock</p>
        <p class="text-2xl sm:text-3xl font-bold mt-1" :class="stats.alert_count > 0 ? 'text-red-700' : 'text-gray-900'">
          {{ stats.alert_count }}
        </p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-gray-500">CA aujourd'hui</p>
        <p class="text-lg sm:text-2xl font-bold text-green-700 mt-1">{{ formatCurrency(stats.sales_today) }}</p>
      </div>
      <div class="card p-4">
        <p class="text-xs text-gray-500">CA ce mois</p>
        <p class="text-lg sm:text-2xl font-bold text-green-700 mt-1">{{ formatCurrency(stats.sales_month) }}</p>
      </div>
    </div>

    <!-- Stock value cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" v-if="stats">
      <div class="card p-4">
        <h3 class="font-semibold text-gray-700 mb-3 text-sm">Stock Dépôt</h3>
        <div class="flex items-end justify-between">
          <div>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.total_depot_stock }}</p>
            <p class="text-xs text-gray-500">unités</p>
          </div>
          <div class="text-right">
            <p class="text-base sm:text-lg font-semibold text-indigo-700">{{ formatCurrency(stats.valeur_depot) }}</p>
            <p class="text-xs text-gray-400">valeur achat</p>
          </div>
        </div>
      </div>
      <div class="card p-4">
        <h3 class="font-semibold text-gray-700 mb-3 text-sm">Stock Boutique</h3>
        <div class="flex items-end justify-between">
          <div>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.total_boutique_stock }}</p>
            <p class="text-xs text-gray-500">unités</p>
          </div>
          <div class="text-right">
            <p class="text-base sm:text-lg font-semibold text-green-700">{{ formatCurrency(stats.valeur_boutique) }}</p>
            <p class="text-xs text-gray-400">valeur vente</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert products -->
    <div v-if="alertProducts.length > 0" class="card p-4 border-red-200">
      <div class="flex items-center gap-2 mb-3">
        <ExclamationTriangleIcon class="w-5 h-5 text-red-500 flex-shrink-0" />
        <h3 class="font-semibold text-red-700 text-sm">Produits en alerte ({{ alertProducts.length }})</h3>
      </div>
      <!-- Mobile: cartes -->
      <div class="sm:hidden space-y-2">
        <div v-for="p in alertProducts" :key="p.id"
          class="flex items-center justify-between bg-red-50 rounded-lg px-3 py-2">
          <div>
            <p class="font-medium text-gray-900 text-sm">{{ p.name }}</p>
            <p class="text-xs text-gray-500">{{ p.category }}</p>
          </div>
          <div class="flex flex-col items-end gap-1">
            <span v-if="p.boutique_alerted" class="badge-alert text-xs">
              Bout. {{ p.stock_boutique }}/{{ p.alert_threshold_boutique }}
            </span>
            <span v-if="p.depot_alerted" class="badge-alert text-xs">
              Dép. {{ p.stock_depot }}/{{ p.alert_threshold_depot }}
            </span>
          </div>
        </div>
      </div>
      <!-- Desktop: table -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left border-b border-gray-100">
              <th class="pb-2 font-medium text-gray-500">Produit</th>
              <th class="pb-2 font-medium text-gray-500">Catégorie</th>
              <th class="pb-2 font-medium text-gray-500 text-center">Dépôt</th>
              <th class="pb-2 font-medium text-gray-500 text-center">Boutique</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="p in alertProducts" :key="p.id">
              <td class="py-2.5 font-medium text-gray-900">{{ p.name }}</td>
              <td class="py-2.5 text-gray-500">{{ p.category }}</td>
              <td class="py-2.5 text-center">
                <span :class="p.depot_alerted ? 'badge-alert' : 'badge-ok'">
                  {{ p.stock_depot }} / {{ p.alert_threshold_depot }}
                </span>
              </td>
              <td class="py-2.5 text-center">
                <span :class="p.boutique_alerted ? 'badge-alert' : 'badge-ok'">
                  {{ p.stock_boutique }} / {{ p.alert_threshold_boutique }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-3 text-sm">Ventes récentes</h3>
        <div v-if="recentSales.length === 0" class="text-center text-gray-400 py-6 text-sm">
          Aucune vente enregistrée
        </div>
        <div v-else class="space-y-2.5">
          <div v-for="sale in recentSales" :key="sale.id" class="flex items-center justify-between text-sm">
            <div class="min-w-0">
              <p class="font-medium text-gray-900 truncate">{{ sale.product?.name }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(sale.created_at) }} · {{ sale.user?.name }}</p>
            </div>
            <div class="text-right ml-3 flex-shrink-0">
              <p class="font-medium text-green-700">{{ formatCurrency(sale.total_price) }}</p>
              <p class="text-xs text-gray-500">x{{ sale.quantity }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-3 text-sm">Mouvements récents</h3>
        <div v-if="recentMovements.length === 0" class="text-center text-gray-400 py-6 text-sm">
          Aucun mouvement enregistré
        </div>
        <div v-else class="space-y-2.5">
          <div v-for="m in recentMovements" :key="m.id" class="flex items-center justify-between text-sm">
            <div class="min-w-0">
              <p class="font-medium text-gray-900 truncate">{{ m.product?.name }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(m.created_at) }}</p>
            </div>
            <span :class="m.type === 'depot_entry' ? 'badge-ok' : 'badge-warning'" class="ml-3 flex-shrink-0 text-xs whitespace-nowrap">
              {{ m.type === 'depot_entry' ? '+Dépôt' : '→Bout.' }} {{ m.quantity }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ArrowPathIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const loading = ref(false)
const stats = ref(null)
const alertProducts = ref([])
const recentSales = ref([])
const recentMovements = ref([])

async function loadData() {
  loading.value = true
  try {
    const { data } = await axios.get('/dashboard')
    stats.value = data.stats
    alertProducts.value = data.alert_products
    recentSales.value = data.recent_sales
    recentMovements.value = data.recent_movements
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function formatCurrency(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0) + ' FCFA'
}

function formatDate(d) {
  return new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

onMounted(loadData)
</script>
