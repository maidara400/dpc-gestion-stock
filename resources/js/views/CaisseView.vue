<template>
  <div class="space-y-4">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Caisse</h1>

    <!-- Filtres -->
    <div class="flex gap-2">
      <input v-model="search" type="text" class="input flex-1" placeholder="Filtrer les produits..." autocomplete="off" />
      <select v-model="categoryFilter" class="input w-36 sm:w-44 flex-shrink-0">
        <option value="">Toutes</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

      <!-- Grille produits -->
      <div class="lg:col-span-2 space-y-4">
        <div v-if="filteredGroups.length === 0" class="card p-10 text-center text-gray-400 text-sm">
          Aucun produit en stock boutique.
        </div>
        <div v-for="group in filteredGroups" :key="group.category_id">
          <div class="flex items-center gap-2 mb-2">
            <span class="inline-block w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: group.color }"></span>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ group.category_name }}</p>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <button
              v-for="p in group.products" :key="p.id"
              @click="addToCart(p)"
              class="text-left p-3 rounded-xl border transition-all active:scale-95 relative"
              :class="cartQty(p.id) > 0
                ? 'border-green-400 bg-green-50 shadow-sm ring-2 ring-green-300'
                : 'border-gray-200 bg-white hover:border-green-300 hover:bg-green-50/50'"
            >
              <!-- Badge quantité dans le panier -->
              <span v-if="cartQty(p.id) > 0"
                class="absolute -top-2 -right-2 w-5 h-5 bg-green-600 text-white text-xs font-bold rounded-full flex items-center justify-center shadow">
                {{ cartQty(p.id) }}
              </span>
              <p class="font-semibold text-gray-900 text-sm leading-tight line-clamp-2">{{ p.name }}</p>
              <p v-if="p.sku" class="text-xs text-gray-400 mt-0.5">{{ p.sku }}</p>
              <div class="flex items-end justify-between mt-2 gap-1">
                <span class="text-sm font-bold text-green-700 leading-none">{{ formatCurrency(p.sale_price) }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium flex-shrink-0"
                  :class="p.boutique_alerted ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                  {{ p.stock_boutique }}
                </span>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Panneau panier + validation -->
      <div class="lg:col-span-1 space-y-3 lg:sticky lg:top-6">

        <!-- Panier vide -->
        <div v-if="cart.length === 0" class="card p-8 text-center text-gray-400 text-sm border-2 border-dashed border-gray-200">
          <ShoppingCartIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
          <p>Toucher un produit pour l'ajouter</p>
        </div>

        <!-- Panier rempli -->
        <div v-else class="card p-0 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="font-semibold text-gray-900 text-sm">Panier ({{ cart.length }} article{{ cart.length > 1 ? 's' : '' }})</p>
            <button @click="clearCart" class="text-xs text-red-500 hover:text-red-700">Vider</button>
          </div>

          <!-- Lignes du panier -->
          <div class="divide-y divide-gray-50">
            <div v-for="item in cart" :key="item.product.id" class="px-4 py-2.5 flex items-center gap-2">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ item.product.name }}</p>
                <p class="text-xs text-gray-400">{{ formatCurrency(item.product.sale_price) }} / u</p>
              </div>
              <div class="flex items-center gap-1.5 flex-shrink-0">
                <button @click="decrementCart(item.product)"
                  class="w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-600 font-bold flex items-center justify-center hover:bg-gray-50 text-sm">
                  −
                </button>
                <span class="w-7 text-center text-sm font-bold text-gray-900">{{ item.quantity }}</span>
                <button @click="incrementCart(item.product)"
                  :disabled="item.quantity >= item.product.stock_boutique"
                  class="w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-600 font-bold flex items-center justify-center hover:bg-gray-50 disabled:opacity-40 text-sm">
                  +
                </button>
              </div>
              <p class="w-24 text-right text-sm font-semibold text-green-700 flex-shrink-0">
                {{ formatCurrency(item.product.sale_price * item.quantity) }}
              </p>
            </div>
          </div>

          <!-- Total -->
          <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-700">Total</p>
            <p class="text-xl font-bold text-green-700">{{ formatCurrency(cartTotal) }}</p>
          </div>
        </div>

        <!-- Formulaire ticket + paiement -->
        <div v-if="cart.length > 0" class="card p-4 space-y-3">
          <div>
            <label class="label">N° Ticket (optionnel)</label>
            <input v-model="ticketNumber" type="text" class="input" placeholder="TK-001..." />
          </div>
          <div>
            <label class="label">Mode de paiement</label>
            <div class="grid grid-cols-3 gap-2 mt-1">
              <button v-for="pm in paymentMethods" :key="pm.value"
                @click="paymentMethod = pm.value"
                class="flex flex-col items-center gap-1.5 py-3 rounded-xl border-2 transition-all text-sm font-semibold"
                :class="paymentMethod === pm.value
                  ? 'border-green-400 bg-green-50 text-green-700'
                  : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'">
                <span class="text-lg">{{ pm.icon }}</span>
                {{ pm.label }}
              </button>
            </div>
          </div>

          <div v-if="saleError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-3 py-2">
            {{ saleError }}
          </div>

          <button
            @click="confirmSale"
            class="btn-primary w-full justify-center py-3.5 text-base"
            :disabled="loading || !paymentMethod"
          >
            <span v-if="loading">Enregistrement...</span>
            <span v-else-if="!paymentMethod">Choisir un mode de paiement</span>
            <span v-else>Valider · {{ formatCurrency(cartTotal) }}</span>
          </button>
        </div>

        <!-- Ventes de la session -->
        <div class="card p-0 overflow-hidden" v-if="sessionTickets.length > 0">
          <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-700">Session</p>
            <p class="text-sm font-bold text-green-700">{{ formatCurrency(sessionTotal) }}</p>
          </div>
          <div class="divide-y divide-gray-50 max-h-56 overflow-y-auto">
            <div v-for="ticket in sessionTickets" :key="ticket.id" class="px-4 py-2.5">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <p class="text-xs font-semibold text-gray-700">
                    {{ ticket.ticket_number ? `#${ticket.ticket_number}` : 'Vente' }}
                    <span class="ml-1 font-normal text-gray-400">· {{ ticket.time }}</span>
                  </p>
                  <p class="text-xs text-gray-400">{{ ticket.lines.length }} article{{ ticket.lines.length > 1 ? 's' : '' }}
                    · <span class="font-medium">{{ paymentLabel(ticket.payment_method) }}</span>
                  </p>
                </div>
                <p class="text-sm font-bold text-green-700 flex-shrink-0">{{ formatCurrency(ticket.total) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div v-if="successMessage"
        class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:w-auto bg-green-600 text-white px-5 py-4 rounded-xl shadow-xl font-medium text-sm z-50">
        ✓ {{ successMessage }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { ShoppingCartIcon } from '@heroicons/vue/24/outline'

const products = ref([])
const categories = ref([])
const search = ref('')
const categoryFilter = ref('')
const cart = ref([])
const ticketNumber = ref('')
const paymentMethod = ref('')
const loading = ref(false)
const saleError = ref('')
const successMessage = ref('')
const sessionTickets = ref([])

const paymentMethods = [
  { value: 'wave', label: 'Wave', icon: '🌊' },
  { value: 'om', label: 'OM', icon: '🟠' },
  { value: 'cash', label: 'Liquide', icon: '💵' },
]

async function loadProducts() {
  const { data } = await axios.get('/products')
  products.value = data
}

async function loadCategories() {
  try {
    const { data } = await axios.get('/categories')
    categories.value = data
  } catch {}
}

const filteredGroups = computed(() => {
  const term = search.value.toLowerCase()
  const filtered = products.value.filter(p => {
    const matchStock = p.stock_boutique > 0
    const matchSearch = !term || p.name.toLowerCase().includes(term) || (p.sku || '').toLowerCase().includes(term)
    const matchCat = !categoryFilter.value || p.category?.id === categoryFilter.value
    return matchStock && matchSearch && matchCat
  })
  const groups = {}
  filtered.forEach(p => {
    const key = p.category?.id || 0
    if (!groups[key]) groups[key] = {
      category_id: key,
      category_name: p.category?.name || 'Sans catégorie',
      color: p.category?.color || '#6b7280',
      products: [],
    }
    groups[key].products.push(p)
  })
  return Object.values(groups).sort((a, b) => a.category_name.localeCompare(b.category_name))
})

function cartQty(productId) {
  return cart.value.find(i => i.product.id === productId)?.quantity || 0
}

function addToCart(product) {
  const existing = cart.value.find(i => i.product.id === product.id)
  if (existing) {
    if (existing.quantity < product.stock_boutique) existing.quantity++
  } else {
    cart.value.push({ product, quantity: 1 })
  }
}

function incrementCart(product) {
  const item = cart.value.find(i => i.product.id === product.id)
  if (item && item.quantity < product.stock_boutique) item.quantity++
}

function decrementCart(product) {
  const idx = cart.value.findIndex(i => i.product.id === product.id)
  if (idx === -1) return
  if (cart.value[idx].quantity > 1) cart.value[idx].quantity--
  else cart.value.splice(idx, 1)
}

function clearCart() {
  cart.value = []
  ticketNumber.value = ''
  paymentMethod.value = ''
  saleError.value = ''
}

const cartTotal = computed(() =>
  cart.value.reduce((sum, i) => sum + i.product.sale_price * i.quantity, 0)
)

const sessionTotal = computed(() =>
  sessionTickets.value.reduce((sum, t) => sum + t.total, 0)
)

async function confirmSale() {
  if (cart.value.length === 0 || !paymentMethod.value) return
  loading.value = true
  saleError.value = ''
  try {
    const { data } = await axios.post('/sales/batch', {
      items: cart.value.map(i => ({ product_id: i.product.id, quantity: i.quantity })),
      ticket_number: ticketNumber.value || null,
      payment_method: paymentMethod.value,
    })

    // Mise à jour stock local
    data.sales.forEach(sale => {
      const p = products.value.find(p => p.id === sale.product_id)
      if (p) {
        p.stock_boutique -= sale.quantity
        p.boutique_alerted = p.stock_boutique <= p.alert_threshold_boutique
      }
    })

    // Ajout au récap session
    sessionTickets.value.unshift({
      id: Date.now(),
      ticket_number: ticketNumber.value || null,
      payment_method: paymentMethod.value,
      time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
      lines: data.sales,
      total: data.total,
    })

    successMessage.value = `${cart.value.length} article${cart.value.length > 1 ? 's' : ''} · ${formatCurrency(cartTotal.value)}`
    setTimeout(() => successMessage.value = '', 3000)
    clearCart()
  } catch (e) {
    saleError.value = e.response?.data?.message || 'Erreur lors de la vente.'
  } finally {
    loading.value = false
  }
}

function paymentLabel(v) {
  return { wave: 'Wave', om: 'Orange Money', cash: 'Liquide' }[v] || v
}

function formatCurrency(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0) + ' FCFA'
}

onMounted(() => Promise.all([loadProducts(), loadCategories()]))
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all .3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
