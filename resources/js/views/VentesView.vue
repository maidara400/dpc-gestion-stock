<template>
  <div class="space-y-5">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Historique des ventes</h1>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Période</label>
          <select v-model="filters.period" class="input" @change="filters.from = ''; filters.to = ''; loadSales()">
            <option value="">Toutes</option>
            <option value="day">Aujourd'hui</option>
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
          </select>
        </div>
        <div>
          <label class="label">Du</label>
          <input v-model="filters.from" type="date" class="input" @change="filters.period = ''; loadSales()" />
        </div>
        <div>
          <label class="label">Au</label>
          <input v-model="filters.to" type="date" class="input" @change="filters.period = ''; loadSales()" />
        </div>
        <div class="col-span-2 sm:col-span-1">
          <label class="label">Catégorie</label>
          <select v-model="filters.category_id" class="input" @change="loadSales()">
            <option value="">Toutes</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-3 gap-3" v-if="summary">
      <div class="card p-3 sm:p-5 text-center">
        <p class="text-xs text-gray-500">Ventes</p>
        <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ summary.total_sales }}</p>
      </div>
      <div class="card p-3 sm:p-5 text-center">
        <p class="text-xs text-gray-500">Articles</p>
        <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ summary.total_items_sold }}</p>
      </div>
      <div class="card p-3 sm:p-5 text-center">
        <p class="text-xs text-gray-500">CA total</p>
        <p class="text-lg sm:text-2xl font-bold text-green-700 mt-1">{{ formatCurrency(summary.total_revenue) }}</p>
      </div>
    </div>

    <!-- Mobile: cartes ventes -->
    <div class="sm:hidden space-y-2">
      <div v-for="sale in sales" :key="sale.id" class="card p-3">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="font-semibold text-gray-900 text-sm truncate">{{ sale.product?.name }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(sale.created_at) }} · {{ sale.user?.name }}</p>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="font-bold text-green-700">{{ formatCurrency(sale.total_price) }}</p>
            <p class="text-xs text-gray-500">x{{ sale.quantity }} · {{ formatCurrency(sale.unit_price) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 mt-2">
          <span v-if="sale.product?.category" class="inline-block px-2 py-0.5 rounded-full text-xs font-medium text-white"
            :style="{ backgroundColor: sale.product?.category?.color || '#6b7280' }">
            {{ sale.product?.category?.name }}
          </span>
          <span class="text-xs text-gray-400 font-mono">#{{ sale.id }}</span>
        </div>
      </div>
      <p v-if="sales.length === 0" class="text-center text-gray-400 py-10 text-sm">Aucune vente sur cette période</p>
    </div>

    <!-- Desktop: table -->
    <div class="hidden sm:block card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="px-4 py-3 font-medium text-gray-500">ID</th>
              <th class="px-4 py-3 font-medium text-gray-500">Date/Heure</th>
              <th class="px-4 py-3 font-medium text-gray-500">Produit</th>
              <th class="px-4 py-3 font-medium text-gray-500">Catégorie</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Qté</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-right">Prix unit.</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-right">Total</th>
              <th class="px-4 py-3 font-medium text-gray-500">Caissier</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="sale in sales" :key="sale.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-400 font-mono text-xs">#{{ sale.id }}</td>
              <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ formatDate(sale.created_at) }}</td>
              <td class="px-4 py-3 font-medium text-gray-900">{{ sale.product?.name }}</td>
              <td class="px-4 py-3">
                <span v-if="sale.product?.category" class="inline-block px-2 py-0.5 rounded-full text-xs font-medium text-white"
                  :style="{ backgroundColor: sale.product?.category?.color || '#6b7280' }">
                  {{ sale.product?.category?.name }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">{{ sale.quantity }}</td>
              <td class="px-4 py-3 text-right text-gray-600">{{ formatCurrency(sale.unit_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-green-700">{{ formatCurrency(sale.total_price) }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ sale.user?.name }}</td>
            </tr>
          </tbody>
        </table>
        <p v-if="sales.length === 0" class="text-center text-gray-400 py-12">Aucune vente sur cette période</p>
      </div>

      <div v-if="pagination && pagination.last_page > 1"
        class="px-4 py-3 border-t border-gray-100 flex items-center justify-between text-sm">
        <span class="text-gray-500">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
            class="btn-secondary py-1 px-3 text-xs">Préc.</button>
          <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
            class="btn-secondary py-1 px-3 text-xs">Suiv.</button>
        </div>
      </div>
    </div>

    <!-- Mobile pagination -->
    <div v-if="pagination && pagination.last_page > 1"
      class="sm:hidden flex items-center justify-between">
      <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
        class="btn-secondary py-2 px-4 text-sm">← Préc.</button>
      <span class="text-sm text-gray-500">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
        class="btn-secondary py-2 px-4 text-sm">Suiv. →</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const sales = ref([])
const categories = ref([])
const summary = ref(null)
const pagination = ref(null)
const filters = ref({ period: 'month', from: '', to: '', category_id: '' })

async function loadSales(page = 1) {
  const params = { page, ...Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v !== '')) }
  const { data } = await axios.get('/sales/history', { params })
  sales.value = data.data?.data || []
  pagination.value = data.data
  summary.value = data.summary
}

async function loadCategories() {
  const { data } = await axios.get('/categories')
  categories.value = data
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  loadSales(page)
}

function formatCurrency(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0) + ' FCFA'
}

function formatDate(d) {
  return new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => Promise.all([loadSales(), loadCategories()]))
</script>
