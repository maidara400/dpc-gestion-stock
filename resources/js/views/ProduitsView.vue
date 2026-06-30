<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Produits</h1>
      <button @click="openCreateModal" class="btn-primary flex-shrink-0">
        <PlusIcon class="w-4 h-4" />
        <span class="hidden sm:inline">Nouveau produit</span>
        <span class="sm:hidden">Nouveau</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
      <input v-model="searchTerm" type="text" class="input flex-1 min-w-[140px]" placeholder="Rechercher..." />
      <select v-model="categoryFilter" class="input w-full sm:w-44">
        <option value="">Toutes les catégories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer self-center">
        <input type="checkbox" v-model="alertOnly" class="rounded border-gray-300 text-green-600" />
        Alertes
      </label>
    </div>

    <!-- Mobile: cartes produits -->
    <div class="sm:hidden space-y-2">
      <div v-for="p in filteredProducts" :key="p.id"
        class="card p-3 border"
        :class="(p.boutique_alerted || p.depot_alerted) ? 'border-red-200 bg-red-50/30' : 'border-gray-100'">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="font-semibold text-gray-900 text-sm truncate">{{ p.name }}</p>
            <p class="text-xs text-gray-400">{{ p.sku || '—' }}</p>
            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium text-white"
              :style="{ backgroundColor: p.category?.color || '#6b7280' }">
              {{ p.category?.name }}
            </span>
          </div>
          <div class="flex gap-1 flex-shrink-0">
            <button @click="openEditModal(p)" class="p-2 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100">
              <PencilSquareIcon class="w-4 h-4" />
            </button>
            <button @click="archiveProduct(p)" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
        <p v-if="p.supplier" class="text-xs text-gray-400 mt-1.5">
          <span class="font-medium text-gray-500">Fourn. :</span> {{ p.supplier.name }}
        </p>
        <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
          <div class="bg-white rounded-lg p-2 text-center border border-gray-100">
            <p class="text-gray-400 mb-0.5">Dépôt</p>
            <span :class="p.depot_alerted ? 'badge-alert' : 'font-semibold text-gray-800'">
              {{ p.stock_depot }}<span class="opacity-50">/{{ p.alert_threshold_depot }}</span>
            </span>
          </div>
          <div class="bg-white rounded-lg p-2 text-center border border-gray-100">
            <p class="text-gray-400 mb-0.5">Boutique</p>
            <span :class="p.boutique_alerted ? 'badge-alert' : 'font-semibold text-gray-800'">
              {{ p.stock_boutique }}<span class="opacity-50">/{{ p.alert_threshold_boutique }}</span>
            </span>
          </div>
          <div class="bg-white rounded-lg p-2 text-center border border-gray-100">
            <p class="text-gray-400 mb-0.5">Achat</p>
            <p class="font-medium text-gray-700">{{ formatCurrency(p.purchase_price) }}</p>
          </div>
          <div class="bg-white rounded-lg p-2 text-center border border-gray-100">
            <p class="text-gray-400 mb-0.5">Vente</p>
            <p class="font-semibold text-green-700">{{ formatCurrency(p.sale_price) }}</p>
          </div>
        </div>
      </div>
      <p v-if="filteredProducts.length === 0" class="text-center text-gray-400 py-10 text-sm">Aucun produit trouvé</p>
    </div>

    <!-- Desktop: table -->
    <div class="hidden sm:block card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="px-4 py-3 font-medium text-gray-500">Produit / SKU</th>
              <th class="px-4 py-3 font-medium text-gray-500">Catégorie</th>
              <th class="px-4 py-3 font-medium text-gray-500">Fournisseur</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Dépôt</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Boutique</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-right">Achat</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-right">Vente</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="p in filteredProducts" :key="p.id" :class="(p.boutique_alerted || p.depot_alerted) ? 'bg-red-50/30' : ''">
              <td class="px-4 py-3">
                <p class="font-medium text-gray-900">{{ p.name }}</p>
                <p class="text-xs text-gray-400">{{ p.sku || '-' }}</p>
              </td>
              <td class="px-4 py-3">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium text-white"
                  :style="{ backgroundColor: p.category?.color || '#6b7280' }">
                  {{ p.category?.name }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ p.supplier?.name || '—' }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="p.depot_alerted ? 'badge-alert' : 'text-gray-700'">
                  {{ p.stock_depot }}<span class="text-xs opacity-60">/{{ p.alert_threshold_depot }}</span>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="p.boutique_alerted ? 'badge-alert' : 'text-gray-700'">
                  {{ p.stock_boutique }}<span class="text-xs opacity-60">/{{ p.alert_threshold_boutique }}</span>
                </span>
              </td>
              <td class="px-4 py-3 text-right text-gray-600">{{ formatCurrency(p.purchase_price) }}</td>
              <td class="px-4 py-3 text-right font-medium text-green-700">{{ formatCurrency(p.sale_price) }}</td>
              <td class="px-4 py-3 text-center">
                <button @click="openEditModal(p)" class="text-gray-400 hover:text-gray-700 p-1 rounded">
                  <PencilSquareIcon class="w-4 h-4" />
                </button>
                <button @click="archiveProduct(p)" class="text-gray-400 hover:text-red-600 p-1 rounded ml-1">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="filteredProducts.length === 0" class="text-center text-gray-400 py-12">Aucun produit trouvé</p>
      </div>
    </div>

    <!-- Modal (fullscreen sur mobile) -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50">
      <div class="bg-white w-full sm:rounded-2xl sm:shadow-2xl sm:w-full sm:max-w-lg rounded-t-2xl max-h-[92vh] overflow-y-auto">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
          <h3 class="font-semibold text-gray-900">{{ editingProduct ? 'Modifier produit' : 'Nouveau produit' }}</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <form @submit.prevent="saveProduct" class="p-5 space-y-4">
          <div>
            <label class="label">Nom du produit *</label>
            <input v-model="form.name" type="text" class="input" required />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label">SKU / Code-barres</label>
              <input v-model="form.sku" type="text" class="input" />
            </div>
            <div>
              <label class="label">Catégorie *</label>
              <select v-model="form.category_id" class="input" required>
                <option value="">Choisir...</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="label">Fournisseur</label>
              <select v-model="form.supplier_id" class="input">
                <option value="">Aucun fournisseur</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Prix achat (€) *</label>
              <input v-model.number="form.purchase_price" type="number" step="0.01" min="0" class="input" required />
            </div>
            <div>
              <label class="label">Prix vente (€) *</label>
              <input v-model.number="form.sale_price" type="number" step="0.01" min="0" class="input" required />
            </div>
            <div>
              <label class="label">Seuil alerte Dépôt</label>
              <input v-model.number="form.alert_threshold_depot" type="number" min="0" class="input" />
            </div>
            <div>
              <label class="label">Seuil alerte Boutique</label>
              <input v-model.number="form.alert_threshold_boutique" type="number" min="0" class="input" />
            </div>
          </div>
          <div v-if="formError" class="text-red-600 text-sm">{{ formError }}</div>
          <div class="flex gap-3 pt-1">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1 justify-center">Annuler</button>
            <button type="submit" class="btn-primary flex-1 justify-center" :disabled="formLoading">
              {{ formLoading ? 'Sauvegarde...' : 'Sauvegarder' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const products = ref([])
const categories = ref([])
const suppliers = ref([])
const searchTerm = ref('')
const categoryFilter = ref('')
const alertOnly = ref(false)
const showModal = ref(false)
const editingProduct = ref(null)
const formLoading = ref(false)
const formError = ref('')

const defaultForm = () => ({
  name: '', sku: '', category_id: '', supplier_id: '',
  purchase_price: 0, sale_price: 0,
  alert_threshold_depot: 5, alert_threshold_boutique: 3,
})
const form = ref(defaultForm())

const filteredProducts = computed(() =>
  products.value.filter(p => {
    const matchSearch = !searchTerm.value ||
      p.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      (p.sku || '').toLowerCase().includes(searchTerm.value.toLowerCase())
    const matchCategory = !categoryFilter.value || p.category?.id === categoryFilter.value
    const matchAlert = !alertOnly.value || p.boutique_alerted || p.depot_alerted
    return matchSearch && matchCategory && matchAlert
  })
)

async function loadData() {
  const [p, c, s] = await Promise.all([axios.get('/products'), axios.get('/categories'), axios.get('/suppliers')])
  products.value = p.data
  categories.value = c.data
  suppliers.value = s.data
}

function openCreateModal() {
  editingProduct.value = null
  form.value = defaultForm()
  formError.value = ''
  showModal.value = true
}

function openEditModal(product) {
  editingProduct.value = product
  form.value = {
    name: product.name, sku: product.sku || '',
    category_id: product.category?.id,
    supplier_id: product.supplier?.id || '',
    purchase_price: product.purchase_price, sale_price: product.sale_price,
    alert_threshold_depot: product.alert_threshold_depot,
    alert_threshold_boutique: product.alert_threshold_boutique,
  }
  formError.value = ''
  showModal.value = true
}

async function saveProduct() {
  formLoading.value = true; formError.value = ''
  try {
    if (editingProduct.value) {
      await axios.put(`/products/${editingProduct.value.id}`, form.value)
    } else {
      await axios.post('/products', form.value)
    }
    showModal.value = false
    await loadData()
  } catch (e) {
    const errors = e.response?.data?.errors
    formError.value = errors ? Object.values(errors).flat().join(', ') : e.response?.data?.message || 'Erreur'
  } finally { formLoading.value = false }
}

async function archiveProduct(product) {
  if (!confirm(`Archiver "${product.name}" ?`)) return
  await axios.delete(`/products/${product.id}`)
  await loadData()
}

function formatCurrency(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0) + ' FCFA'
}

onMounted(loadData)
</script>
