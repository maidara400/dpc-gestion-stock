<template>
  <div class="space-y-5">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Inventaire & Mouvements</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <!-- Entrée dépôt -->
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
          <span class="w-6 h-6 bg-indigo-100 text-indigo-700 rounded flex items-center justify-center text-xs font-bold flex-shrink-0">D</span>
          Entrée Dépôt (Achat fournisseur)
        </h3>
        <form @submit.prevent="submitDepot" class="space-y-3">
          <div>
            <label class="label">Produit</label>
            <select v-model="depotForm.product_id" class="input" required>
              <option value="">Sélectionner un produit...</option>
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.name }} (Dépôt: {{ p.stock_depot }})
              </option>
            </select>
          </div>
          <div>
            <label class="label">Fournisseur</label>
            <select v-model="depotForm.supplier_id" class="input" @change="onSupplierChange">
              <option value="">Aucun fournisseur</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">
                {{ s.name }}
                <template v-if="s.payment_terms && s.payment_terms !== 'immediate'"> — {{ paymentTermsLabel(s.payment_terms) }}</template>
              </option>
            </select>
          </div>
          <div>
            <label class="label">Quantité reçue</label>
            <input v-model.number="depotForm.quantity" type="number" min="1" class="input" required />
          </div>

          <!-- Bloc paiement -->
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-3">
            <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Paiement</p>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label">Montant à payer (FCFA)</label>
                <input v-model.number="depotForm.amount_due" type="number" step="0.01" min="0"
                  class="input" placeholder="0.00" />
              </div>
              <div>
                <label class="label">Date d'échéance</label>
                <input v-model="depotForm.payment_due_date" type="date" class="input" />
              </div>
            </div>
            <p v-if="selectedSupplierTerms" class="text-xs text-indigo-600">
              Conditions {{ selectedSupplierTerms.name }} :
              <strong>{{ paymentTermsLabel(selectedSupplierTerms.payment_terms) }}</strong>
              <template v-if="depotForm.payment_due_date">
                → échéance auto fixée au {{ formatDate(depotForm.payment_due_date) }}
              </template>
            </p>
            <p v-else-if="!depotForm.payment_due_date && depotForm.amount_due > 0" class="text-xs text-gray-400">
              Sans date d'échéance, le paiement sera considéré comme immédiat.
            </p>
          </div>

          <div>
            <label class="label">Notes (optionnel)</label>
            <input v-model="depotForm.notes" type="text" class="input" placeholder="N° bon de livraison..." />
          </div>
          <div v-if="depotError" class="text-red-600 text-sm">{{ depotError }}</div>
          <button type="submit" class="btn-primary w-full justify-center" :disabled="depotLoading">
            {{ depotLoading ? 'Enregistrement...' : 'Valider l\'entrée dépôt' }}
          </button>
        </form>
      </div>

      <!-- Inventaire initial boutique -->
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
          <span class="w-6 h-6 bg-purple-100 text-purple-700 rounded flex items-center justify-center text-xs font-bold flex-shrink-0">B</span>
          Stock Initial Boutique
        </h3>
        <p class="text-xs text-gray-500 mb-3">Pour saisir un stock déjà présent en boutique sans passer par le dépôt.</p>
        <form @submit.prevent="submitInitialBoutique" class="space-y-3">
          <div>
            <label class="label">Produit</label>
            <select v-model="initialBoutiqueForm.product_id" class="input" required>
              <option value="">Sélectionner un produit...</option>
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.name }} (Boutique: {{ p.stock_boutique }})
              </option>
            </select>
          </div>
          <div>
            <label class="label">Quantité en boutique</label>
            <input v-model.number="initialBoutiqueForm.quantity" type="number" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Notes (optionnel)</label>
            <input v-model="initialBoutiqueForm.notes" type="text" class="input" placeholder="Inventaire initial..." />
          </div>
          <div v-if="initialBoutiqueError" class="text-red-600 text-sm">{{ initialBoutiqueError }}</div>
          <button type="submit" class="btn-primary w-full justify-center" :disabled="initialBoutiqueLoading">
            {{ initialBoutiqueLoading ? 'Enregistrement...' : 'Valider le stock initial' }}
          </button>
        </form>
      </div>

      <!-- Transfert -->
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
          <span class="w-6 h-6 bg-green-100 text-green-700 rounded flex items-center justify-center text-xs font-bold flex-shrink-0">→</span>
          Transfert Dépôt → Boutique
        </h3>
        <form @submit.prevent="submitTransfer" class="space-y-3">
          <div>
            <label class="label">Produit</label>
            <select v-model="transferForm.product_id" class="input" required @change="updateTransferProduct">
              <option value="">Sélectionner un produit...</option>
              <option v-for="p in products" :key="p.id" :value="p.id">
                {{ p.name }} (Dépôt: {{ p.stock_depot }})
              </option>
            </select>
          </div>
          <div v-if="selectedTransferProduct" class="text-sm bg-gray-50 rounded-lg p-3 grid grid-cols-2 gap-1">
            <span class="text-gray-500">Dépôt dispo :</span>
            <span class="font-semibold text-right">{{ selectedTransferProduct.stock_depot }}</span>
            <span class="text-gray-500">Boutique actuel :</span>
            <span class="font-semibold text-right">{{ selectedTransferProduct.stock_boutique }}</span>
          </div>
          <div>
            <label class="label">Quantité à transférer</label>
            <input v-model.number="transferForm.quantity" type="number" min="1"
              :max="selectedTransferProduct?.stock_depot" class="input" required />
          </div>
          <div>
            <label class="label">Notes (optionnel)</label>
            <input v-model="transferForm.notes" type="text" class="input" placeholder="Motif du transfert..." />
          </div>
          <div v-if="transferError" class="text-red-600 text-sm">{{ transferError }}</div>
          <button type="submit" class="btn-primary w-full justify-center" :disabled="transferLoading">
            {{ transferLoading ? 'Transfert...' : 'Valider le transfert' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Créances en attente -->
    <div v-if="pendingPayments.length > 0" class="card p-0 overflow-hidden border border-orange-200">
      <div class="flex items-center justify-between px-4 py-3 border-b border-orange-100 bg-orange-50">
        <div class="flex items-center gap-2">
          <span class="w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">!</span>
          <h3 class="font-semibold text-orange-900 text-sm">Paiements en attente</h3>
          <span class="text-xs text-orange-700 font-medium">({{ pendingPayments.length }})</span>
        </div>
        <span class="text-sm font-bold text-orange-800">Total : {{ formatCurrency(totalPending) }}</span>
      </div>

      <!-- Mobile -->
      <div class="sm:hidden divide-y divide-orange-50">
        <div v-for="m in pendingPayments" :key="m.id" class="px-4 py-3">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-medium text-gray-900 text-sm truncate">{{ m.product_name }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ m.supplier_name || 'Sans fournisseur' }} · x{{ m.quantity }}</p>
              <div class="flex items-center gap-2 mt-1">
                <span :class="m.is_overdue ? 'badge-alert' : 'badge-warning'" class="text-xs">
                  {{ m.is_overdue ? 'En retard' : 'En attente' }}
                </span>
                <span v-if="m.payment_due_date" class="text-xs text-gray-500">
                  Échéance : {{ formatDate(m.payment_due_date) }}
                </span>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2 flex-shrink-0">
              <span class="font-bold text-gray-800">{{ formatCurrency(m.amount_due) }}</span>
              <button @click="markPaid(m)" class="btn-primary py-1 px-3 text-xs">Payé ✓</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left border-b border-orange-100 bg-orange-50/50">
              <th class="px-4 py-2 font-medium text-gray-500">Produit</th>
              <th class="px-4 py-2 font-medium text-gray-500">Fournisseur</th>
              <th class="px-4 py-2 font-medium text-gray-500 text-center">Qté</th>
              <th class="px-4 py-2 font-medium text-gray-500 text-right">Montant dû</th>
              <th class="px-4 py-2 font-medium text-gray-500">Échéance</th>
              <th class="px-4 py-2 font-medium text-gray-500">Statut</th>
              <th class="px-4 py-2 font-medium text-gray-500"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-orange-50">
            <tr v-for="m in pendingPayments" :key="m.id" :class="m.is_overdue ? 'bg-red-50/30' : ''">
              <td class="px-4 py-2.5 font-medium text-gray-900">{{ m.product_name }}</td>
              <td class="px-4 py-2.5 text-gray-500">{{ m.supplier_name || '—' }}</td>
              <td class="px-4 py-2.5 text-center">{{ m.quantity }}</td>
              <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ formatCurrency(m.amount_due) }}</td>
              <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                {{ m.payment_due_date ? formatDate(m.payment_due_date) : '—' }}
              </td>
              <td class="px-4 py-2.5">
                <span :class="m.is_overdue ? 'badge-alert' : 'badge-warning'">
                  {{ m.is_overdue ? 'En retard' : 'En attente' }}
                </span>
              </td>
              <td class="px-4 py-2.5">
                <button @click="markPaid(m)" class="btn-primary py-1 px-3 text-xs">Payé ✓</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Historique -->
    <div class="card p-0 overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900 text-sm">Historique des mouvements</h3>
        <select v-model="movementFilter" @change="loadMovements" class="input w-36 text-xs py-1.5">
          <option value="">Tous</option>
          <option value="depot_entry">Entrées dépôt</option>
          <option value="transfer">Transferts</option>
          <option value="boutique_initial">Stock initial</option>
        </select>
      </div>

      <!-- Mobile: cartes -->
      <div class="sm:hidden divide-y divide-gray-50">
        <div v-for="m in movements" :key="m.id" class="px-4 py-3">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-medium text-gray-900 text-sm truncate">{{ m.product?.name }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ formatDatetime(m.created_at) }} · {{ m.user?.name }}</p>
              <p v-if="m.supplier" class="text-xs text-gray-400 mt-0.5">{{ m.supplier.name }}</p>
            </div>
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
              <span :class="m.type === 'depot_entry' ? 'badge-ok' : m.type === 'boutique_initial' ? 'badge-info' : 'badge-warning'" class="text-xs">
                {{ m.type === 'depot_entry' ? 'Entrée' : m.type === 'boutique_initial' ? 'Stock initial' : 'Transfert' }}
              </span>
              <span class="text-sm font-semibold text-gray-700">+{{ m.quantity }}</span>
              <span v-if="m.payment_status" :class="paymentStatusClass(m.payment_status)" class="text-xs">
                {{ paymentStatusLabel(m.payment_status) }}
              </span>
            </div>
          </div>
          <div class="flex gap-3 mt-1.5 text-xs text-gray-500">
            <span>Dépôt : <strong>{{ m.stock_depot_after }}</strong></span>
            <span>Boutique : <strong>{{ m.stock_boutique_after }}</strong></span>
            <span v-if="m.amount_due" class="text-orange-600 font-medium">{{ formatCurrency(m.amount_due) }}</span>
          </div>
        </div>
        <p v-if="movements.length === 0" class="text-center text-gray-400 py-8 text-sm">Aucun mouvement</p>
      </div>

      <!-- Desktop: table -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left border-b border-gray-100 bg-gray-50">
              <th class="px-4 py-3 font-medium text-gray-500">Date</th>
              <th class="px-4 py-3 font-medium text-gray-500">Produit</th>
              <th class="px-4 py-3 font-medium text-gray-500">Fournisseur</th>
              <th class="px-4 py-3 font-medium text-gray-500">Type</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Qté</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Dépôt</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-center">Boutique</th>
              <th class="px-4 py-3 font-medium text-gray-500 text-right">Montant dû</th>
              <th class="px-4 py-3 font-medium text-gray-500">Paiement</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="m in movements" :key="m.id">
              <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ formatDatetime(m.created_at) }}</td>
              <td class="px-4 py-3 font-medium text-gray-900">{{ m.product?.name }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ m.supplier?.name || '—' }}</td>
              <td class="px-4 py-3">
                <span :class="m.type === 'depot_entry' ? 'badge-ok' : m.type === 'boutique_initial' ? 'badge-info' : 'badge-warning'">
                  {{ m.type === 'depot_entry' ? 'Entrée Dépôt' : m.type === 'boutique_initial' ? 'Stock Initial Boutique' : 'Transfert' }}
                </span>
              </td>
              <td class="px-4 py-3 text-center font-medium">+{{ m.quantity }}</td>
              <td class="px-4 py-3 text-center">{{ m.stock_depot_after }}</td>
              <td class="px-4 py-3 text-center">{{ m.stock_boutique_after }}</td>
              <td class="px-4 py-3 text-right text-gray-700">
                {{ m.amount_due ? formatCurrency(m.amount_due) : '—' }}
              </td>
              <td class="px-4 py-3">
                <span v-if="m.payment_status" :class="paymentStatusClass(m.payment_status)">
                  {{ paymentStatusLabel(m.payment_status) }}
                </span>
                <span v-else class="text-gray-300">—</span>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="movements.length === 0" class="text-center text-gray-400 py-8">Aucun mouvement</p>
      </div>
    </div>

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

const products = ref([])
const suppliers = ref([])
const movements = ref([])
const movementFilter = ref('')
const pendingPayments = ref([])
const totalPending = ref(0)

const depotForm = ref({ product_id: '', supplier_id: '', quantity: 1, notes: '', amount_due: '', payment_due_date: '' })
const depotLoading = ref(false)
const depotError = ref('')

const transferForm = ref({ product_id: '', quantity: 1, notes: '' })
const transferLoading = ref(false)
const transferError = ref('')

const initialBoutiqueForm = ref({ product_id: '', quantity: 0, notes: '' })
const initialBoutiqueLoading = ref(false)
const initialBoutiqueError = ref('')
const selectedTransferProduct = ref(null)
const successMessage = ref('')

const selectedSupplierTerms = computed(() => {
  if (!depotForm.value.supplier_id) return null
  const s = suppliers.value.find(s => s.id === depotForm.value.supplier_id)
  return (s && s.payment_terms && s.payment_terms !== 'immediate') ? s : null
})

function onSupplierChange() {
  const s = suppliers.value.find(s => s.id === depotForm.value.supplier_id)
  if (!s) { depotForm.value.payment_due_date = ''; return }
  const days = { '30j': 30, '60j': 60, '90j': 90 }[s.payment_terms]
  if (days) {
    const d = new Date()
    d.setDate(d.getDate() + days)
    depotForm.value.payment_due_date = d.toISOString().split('T')[0]
  } else {
    depotForm.value.payment_due_date = ''
  }
}

async function loadProducts() {
  const { data } = await axios.get('/products')
  products.value = data
}

async function loadSuppliers() {
  const { data } = await axios.get('/suppliers')
  suppliers.value = data
}

async function loadMovements() {
  const params = movementFilter.value ? { type: movementFilter.value } : {}
  const { data } = await axios.get('/stock/movements', { params })
  movements.value = data.data || []
}

async function loadPendingPayments() {
  const { data } = await axios.get('/stock/pending-payments')
  pendingPayments.value = data.movements || []
  totalPending.value = data.total_pending || 0
}

function updateTransferProduct() {
  selectedTransferProduct.value = products.value.find(p => p.id === transferForm.value.product_id)
}

async function submitDepot() {
  depotLoading.value = true; depotError.value = ''
  try {
    const payload = {
      product_id: depotForm.value.product_id,
      quantity: depotForm.value.quantity,
      notes: depotForm.value.notes || null,
      supplier_id: depotForm.value.supplier_id || null,
      amount_due: depotForm.value.amount_due || null,
      payment_due_date: depotForm.value.payment_due_date || null,
    }
    await axios.post('/stock/add-depot', payload)
    successMessage.value = 'Entrée dépôt enregistrée !'
    setTimeout(() => successMessage.value = '', 3000)
    depotForm.value = { product_id: '', supplier_id: '', quantity: 1, notes: '', amount_due: '', payment_due_date: '' }
    await Promise.all([loadProducts(), loadMovements(), loadPendingPayments()])
  } catch (e) {
    depotError.value = e.response?.data?.message || 'Erreur'
  } finally { depotLoading.value = false }
}

async function submitInitialBoutique() {
  initialBoutiqueLoading.value = true; initialBoutiqueError.value = ''
  try {
    await axios.post('/stock/initial-boutique', {
      product_id: initialBoutiqueForm.value.product_id,
      quantity: initialBoutiqueForm.value.quantity,
      notes: initialBoutiqueForm.value.notes || null,
    })
    successMessage.value = 'Stock initial boutique enregistré !'
    setTimeout(() => successMessage.value = '', 3000)
    initialBoutiqueForm.value = { product_id: '', quantity: 0, notes: '' }
    await Promise.all([loadProducts(), loadMovements()])
  } catch (e) {
    initialBoutiqueError.value = e.response?.data?.message || 'Erreur'
  } finally { initialBoutiqueLoading.value = false }
}

async function submitTransfer() {
  transferLoading.value = true; transferError.value = ''
  try {
    await axios.post('/stock/transfer-to-boutique', transferForm.value)
    successMessage.value = 'Transfert effectué !'
    setTimeout(() => successMessage.value = '', 3000)
    transferForm.value = { product_id: '', quantity: 1, notes: '' }
    selectedTransferProduct.value = null
    await Promise.all([loadProducts(), loadMovements()])
  } catch (e) {
    transferError.value = e.response?.data?.message || 'Erreur'
  } finally { transferLoading.value = false }
}

async function markPaid(movement) {
  await axios.post(`/stock/movements/${movement.id}/mark-paid`)
  successMessage.value = 'Paiement enregistré !'
  setTimeout(() => successMessage.value = '', 3000)
  await Promise.all([loadMovements(), loadPendingPayments()])
}

function paymentTermsLabel(terms) {
  return { immediate: 'Paiement immédiat', '30j': 'Paiement à 30 jours', '60j': 'Paiement à 60 jours', '90j': 'Paiement à 90 jours', custom: 'Conditions personnalisées' }[terms] || terms
}

function paymentStatusLabel(status) {
  return { paid: 'Payé', pending: 'En attente', overdue: 'En retard' }[status] || status
}

function paymentStatusClass(status) {
  return { paid: 'badge-ok', pending: 'badge-warning', overdue: 'badge-alert' }[status] || ''
}

function formatCurrency(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0) + ' FCFA'
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDatetime(d) {
  return new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => Promise.all([loadProducts(), loadSuppliers(), loadMovements(), loadPendingPayments()]))
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all .3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
