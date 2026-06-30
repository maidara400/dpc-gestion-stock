<template>
  <div class="space-y-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Configuration</h1>

    <!-- Tabs — scrollable sur mobile -->
    <div class="border-b border-gray-200 -mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto">
      <nav class="flex gap-4 sm:gap-6 min-w-max" aria-label="Tabs">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          class="pb-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
          :class="activeTab === tab.id
            ? 'border-green-500 text-green-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'"
        >{{ tab.label }}</button>
      </nav>
    </div>

    <!-- Catégories -->
    <div v-if="activeTab === 'categories'" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm">
          {{ editingCategory ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}
        </h3>
        <form @submit.prevent="saveCategory" class="space-y-4">
          <div>
            <label class="label">Nom</label>
            <input v-model="catForm.name" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Couleur</label>
            <div class="flex items-center gap-3">
              <input v-model="catForm.color" type="color" class="h-10 w-16 rounded border border-gray-300 cursor-pointer p-1" />
              <span class="text-sm text-gray-500 font-mono">{{ catForm.color }}</span>
            </div>
          </div>
          <div v-if="catError" class="text-red-600 text-sm">{{ catError }}</div>
          <div class="flex gap-2">
            <button type="submit" class="btn-primary" :disabled="catLoading">
              {{ catLoading ? '...' : (editingCategory ? 'Modifier' : 'Créer') }}
            </button>
            <button v-if="editingCategory" type="button" @click="cancelEditCat" class="btn-secondary">Annuler</button>
          </div>
        </form>
      </div>

      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm">Catégories ({{ categories.length }})</h3>
        <div class="space-y-2">
          <div v-for="c in categories" :key="c.id"
            class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
            <div class="flex items-center gap-3 min-w-0">
              <span class="w-4 h-4 rounded-full flex-shrink-0" :style="{ backgroundColor: c.color }"></span>
              <span class="font-medium text-gray-900 text-sm truncate">{{ c.name }}</span>
              <span class="text-xs text-gray-400 flex-shrink-0">{{ c.products_count }}</span>
            </div>
            <div class="flex gap-1 ml-2 flex-shrink-0">
              <button @click="editCat(c)" class="text-gray-400 hover:text-gray-700 p-1.5 rounded hover:bg-gray-200">
                <PencilSquareIcon class="w-4 h-4" />
              </button>
              <button @click="deleteCategory(c)" class="text-gray-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fournisseurs -->
    <div v-if="activeTab === 'suppliers'" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm">
          {{ editingSupplier ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}
        </h3>
        <form @submit.prevent="saveSupplier" class="space-y-3">
          <div>
            <label class="label">Nom *</label>
            <input v-model="supForm.name" type="text" class="input" required />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="label">Contact</label>
              <input v-model="supForm.contact" type="text" class="input" placeholder="Nom du contact..." />
            </div>
            <div>
              <label class="label">Téléphone</label>
              <input v-model="supForm.phone" type="text" class="input" placeholder="+33..." />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Email</label>
              <input v-model="supForm.email" type="email" class="input" placeholder="contact@fournisseur.com" />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Adresse</label>
              <input v-model="supForm.address" type="text" class="input" placeholder="Adresse..." />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Conditions de paiement</label>
              <select v-model="supForm.payment_terms" class="input">
                <option value="immediate">Paiement immédiat (à la livraison)</option>
                <option value="30j">À 30 jours</option>
                <option value="60j">À 60 jours</option>
                <option value="90j">À 90 jours</option>
                <option value="custom">Personnalisé</option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <label class="label">Notes</label>
              <textarea v-model="supForm.notes" class="input" rows="2" placeholder="Conditions, délais..."></textarea>
            </div>
          </div>
          <div v-if="supError" class="text-red-600 text-sm">{{ supError }}</div>
          <div class="flex gap-2">
            <button type="submit" class="btn-primary" :disabled="supLoading">
              {{ supLoading ? '...' : (editingSupplier ? 'Modifier' : 'Créer') }}
            </button>
            <button v-if="editingSupplier" type="button" @click="cancelEditSup" class="btn-secondary">Annuler</button>
          </div>
        </form>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900 text-sm">Fournisseurs ({{ suppliers.length }})</h3>
        </div>
        <div v-if="suppliers.length === 0" class="px-6 py-8 text-center text-gray-400 text-sm">
          Aucun fournisseur enregistré.
        </div>
        <div v-else class="divide-y divide-gray-50">
          <div v-for="s in suppliers" :key="s.id" class="px-4 py-3">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="font-medium text-gray-900 text-sm">{{ s.name }}</p>
                <p v-if="s.contact" class="text-xs text-gray-500 mt-0.5">{{ s.contact }}</p>
                <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1">
                  <a v-if="s.phone" :href="`tel:${s.phone}`" class="text-xs text-blue-600 hover:underline">{{ s.phone }}</a>
                  <a v-if="s.email" :href="`mailto:${s.email}`" class="text-xs text-blue-600 hover:underline truncate max-w-[180px]">{{ s.email }}</a>
                </div>
                <p v-if="s.payment_terms" class="text-xs mt-1">
                  <span :class="s.payment_terms === 'immediate' ? 'badge-ok' : 'badge-warning'">
                    {{ { immediate: 'Paiement immédiat', '30j': '30 jours', '60j': '60 jours', '90j': '90 jours', custom: 'Personnalisé' }[s.payment_terms] }}
                  </span>
                </p>
                <p v-if="s.notes" class="text-xs text-gray-400 mt-1 italic">{{ s.notes }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ s.products_count }} produit{{ s.products_count !== 1 ? 's' : '' }}</p>
              </div>
              <div class="flex gap-1 flex-shrink-0">
                <button @click="editSup(s)" class="text-gray-400 hover:text-gray-700 p-1.5 rounded hover:bg-gray-100">
                  <PencilSquareIcon class="w-4 h-4" />
                </button>
                <button @click="deleteSupplier(s)" class="text-gray-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Webhooks -->
    <div v-if="activeTab === 'webhooks'" class="space-y-5">
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm">
          {{ editingWebhook ? 'Modifier webhook' : 'Nouveau webhook' }}
        </h3>
        <form @submit.prevent="saveWebhook" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="label">Nom</label>
              <input v-model="whForm.name" type="text" class="input" placeholder="n8n - Alertes stock" required />
            </div>
            <div>
              <label class="label">URL de destination</label>
              <input v-model="whForm.url" type="url" class="input" placeholder="https://..." required />
            </div>
          </div>
          <div>
            <label class="label">Événements</label>
            <div class="flex flex-col sm:flex-row flex-wrap gap-3 mt-2">
              <label v-for="event in availableEvents" :key="event.id"
                class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" :value="event.id" v-model="whForm.events"
                  class="rounded border-gray-300 text-green-600 w-4 h-4" />
                <span class="text-sm">{{ event.label }}</span>
              </label>
            </div>
          </div>
          <div>
            <label class="label">Secret HMAC (optionnel)</label>
            <input v-model="whForm.secret" type="text" class="input" placeholder="Clé secrète..." />
            <p class="text-xs text-gray-400 mt-1">En-tête X-Webhook-Signature : sha256=HMAC(body, secret)</p>
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="whForm.active" id="wh-active"
              class="rounded border-gray-300 text-green-600 w-4 h-4" />
            <label for="wh-active" class="text-sm text-gray-700">Activer ce webhook</label>
          </div>
          <div v-if="whError" class="text-red-600 text-sm">{{ whError }}</div>
          <div class="flex gap-2">
            <button type="submit" class="btn-primary" :disabled="whLoading">
              {{ whLoading ? '...' : (editingWebhook ? 'Modifier' : 'Créer') }}
            </button>
            <button v-if="editingWebhook" type="button" @click="cancelEditWh" class="btn-secondary">Annuler</button>
          </div>
        </form>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900 text-sm">Webhooks configurés ({{ webhooks.length }})</h3>
        </div>
        <div v-if="webhooks.length === 0" class="px-6 py-8 text-center text-gray-400 text-sm">
          Aucun webhook configuré.
        </div>
        <div v-else class="divide-y divide-gray-50">
          <div v-for="wh in webhooks" :key="wh.id" class="px-4 sm:px-6 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span class="font-medium text-gray-900 text-sm">{{ wh.name }}</span>
                  <span :class="wh.active ? 'badge-ok' : 'badge-alert'">
                    {{ wh.active ? 'Actif' : 'Désactivé' }}
                  </span>
                </div>
                <p class="text-xs text-gray-400 font-mono break-all">{{ wh.url }}</p>
                <div class="flex flex-wrap gap-1 mt-2">
                  <span v-for="event in (wh.events || [])" :key="event" class="badge-warning text-xs">{{ event }}</span>
                </div>
              </div>
              <div class="flex gap-1 flex-shrink-0">
                <button @click="editWh(wh)" class="text-gray-400 hover:text-gray-700 p-1.5 rounded hover:bg-gray-100">
                  <PencilSquareIcon class="w-4 h-4" />
                </button>
                <button @click="deleteWebhook(wh)" class="text-gray-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Utilisateurs -->
    <div v-if="activeTab === 'users'" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div class="card p-4">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm">
          {{ editingUser ? 'Modifier utilisateur' : 'Nouvel utilisateur' }}
        </h3>
        <form @submit.prevent="saveUser" class="space-y-4">
          <div>
            <label class="label">Nom complet</label>
            <input v-model="userForm.name" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="userForm.email" type="email" class="input" required />
          </div>
          <div>
            <label class="label">{{ editingUser ? 'Nouveau mot de passe (vide = inchangé)' : 'Mot de passe' }}</label>
            <input v-model="userForm.password" type="password" class="input" :required="!editingUser" />
          </div>
          <div>
            <label class="label">Rôle</label>
            <select v-model="userForm.role" class="input" required>
              <option value="admin">Super Admin</option>
              <option value="caissier">Caissier</option>
            </select>
          </div>
          <div v-if="userError" class="text-red-600 text-sm">{{ userError }}</div>
          <div class="flex gap-2">
            <button type="submit" class="btn-primary" :disabled="userLoading">
              {{ userLoading ? '...' : (editingUser ? 'Modifier' : 'Créer') }}
            </button>
            <button v-if="editingUser" type="button" @click="cancelEditUser" class="btn-secondary">Annuler</button>
          </div>
        </form>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900 text-sm">Utilisateurs ({{ users.length }})</h3>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-for="u in users" :key="u.id" class="px-4 sm:px-6 py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-medium flex-shrink-0"
                :class="u.role === 'admin' ? 'bg-green-600' : 'bg-blue-600'">
                {{ u.name?.charAt(0)?.toUpperCase() }}
              </div>
              <div class="min-w-0">
                <p class="font-medium text-gray-900 text-sm truncate">{{ u.name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ u.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
              <span :class="u.role === 'admin' ? 'badge-ok' : 'badge-warning'" class="hidden sm:inline-flex">{{ u.role }}</span>
              <button @click="editUser(u)" class="text-gray-400 hover:text-gray-700 p-1.5 rounded hover:bg-gray-100">
                <PencilSquareIcon class="w-4 h-4" />
              </button>
              <button @click="deleteUser(u)" class="text-gray-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'

const activeTab = ref('categories')
const tabs = [
  { id: 'categories', label: 'Catégories' },
  { id: 'suppliers', label: 'Fournisseurs' },
  { id: 'webhooks', label: 'Webhooks' },
  { id: 'users', label: 'Utilisateurs' },
]
const availableEvents = [
  { id: 'stock.alert', label: 'stock.alert' },
  { id: 'sale.created', label: 'sale.created' },
  { id: 'stock.moved', label: 'stock.moved' },
]

// Categories
const categories = ref([])
const catForm = ref({ name: '', color: '#6366f1' })
const catLoading = ref(false)
const catError = ref('')
const editingCategory = ref(null)

async function loadCategories() {
  const { data } = await axios.get('/categories')
  categories.value = data
}
function editCat(c) { editingCategory.value = c; catForm.value = { name: c.name, color: c.color } }
function cancelEditCat() { editingCategory.value = null; catForm.value = { name: '', color: '#6366f1' } }
async function saveCategory() {
  catLoading.value = true; catError.value = ''
  try {
    if (editingCategory.value) await axios.put(`/categories/${editingCategory.value.id}`, catForm.value)
    else await axios.post('/categories', catForm.value)
    catForm.value = { name: '', color: '#6366f1' }; editingCategory.value = null
    await loadCategories()
  } catch (e) { catError.value = e.response?.data?.message || 'Erreur' }
  finally { catLoading.value = false }
}
async function deleteCategory(c) {
  if (!confirm(`Supprimer "${c.name}" ?`)) return
  try { await axios.delete(`/categories/${c.id}`); await loadCategories() }
  catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

// Suppliers
const suppliers = ref([])
const supForm = ref({ name: '', contact: '', email: '', phone: '', address: '', notes: '', payment_terms: 'immediate' })
const supLoading = ref(false)
const supError = ref('')
const editingSupplier = ref(null)

async function loadSuppliers() {
  const { data } = await axios.get('/suppliers'); suppliers.value = data
}
function editSup(s) {
  editingSupplier.value = s
  supForm.value = { name: s.name, contact: s.contact || '', email: s.email || '', phone: s.phone || '', address: s.address || '', notes: s.notes || '', payment_terms: s.payment_terms || 'immediate' }
}
function cancelEditSup() {
  editingSupplier.value = null
  supForm.value = { name: '', contact: '', email: '', phone: '', address: '', notes: '', payment_terms: 'immediate' }
}
async function saveSupplier() {
  supLoading.value = true; supError.value = ''
  try {
    if (editingSupplier.value) await axios.put(`/suppliers/${editingSupplier.value.id}`, supForm.value)
    else await axios.post('/suppliers', supForm.value)
    supForm.value = { name: '', contact: '', email: '', phone: '', address: '', notes: '', payment_terms: 'immediate' }; editingSupplier.value = null
    await loadSuppliers()
  } catch (e) { supError.value = e.response?.data?.message || 'Erreur' }
  finally { supLoading.value = false }
}
async function deleteSupplier(s) {
  if (!confirm(`Supprimer "${s.name}" ?`)) return
  try { await axios.delete(`/suppliers/${s.id}`); await loadSuppliers() }
  catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

// Webhooks
const webhooks = ref([])
const whForm = ref({ name: '', url: '', active: true, events: [], secret: '' })
const whLoading = ref(false)
const whError = ref('')
const editingWebhook = ref(null)

async function loadWebhooks() {
  const { data } = await axios.get('/webhooks'); webhooks.value = data
}
function editWh(wh) {
  editingWebhook.value = wh
  whForm.value = { name: wh.name, url: wh.url, active: wh.active, events: [...(wh.events || [])], secret: wh.secret || '' }
}
function cancelEditWh() { editingWebhook.value = null; whForm.value = { name: '', url: '', active: true, events: [], secret: '' } }
async function saveWebhook() {
  whLoading.value = true; whError.value = ''
  try {
    if (editingWebhook.value) await axios.put(`/webhooks/${editingWebhook.value.id}`, whForm.value)
    else await axios.post('/webhooks', whForm.value)
    whForm.value = { name: '', url: '', active: true, events: [], secret: '' }; editingWebhook.value = null
    await loadWebhooks()
  } catch (e) { whError.value = e.response?.data?.message || 'Erreur' }
  finally { whLoading.value = false }
}
async function deleteWebhook(wh) {
  if (!confirm(`Supprimer "${wh.name}" ?`)) return
  await axios.delete(`/webhooks/${wh.id}`); await loadWebhooks()
}

// Users
const users = ref([])
const userForm = ref({ name: '', email: '', password: '', role: 'caissier' })
const userLoading = ref(false)
const userError = ref('')
const editingUser = ref(null)

async function loadUsers() {
  const { data } = await axios.get('/users'); users.value = data
}
function editUser(u) {
  editingUser.value = u; userForm.value = { name: u.name, email: u.email, password: '', role: u.role }
}
function cancelEditUser() { editingUser.value = null; userForm.value = { name: '', email: '', password: '', role: 'caissier' } }
async function saveUser() {
  userLoading.value = true; userError.value = ''
  try {
    const payload = { ...userForm.value }
    if (editingUser.value && !payload.password) delete payload.password
    if (editingUser.value) await axios.put(`/users/${editingUser.value.id}`, payload)
    else await axios.post('/users', payload)
    userForm.value = { name: '', email: '', password: '', role: 'caissier' }; editingUser.value = null
    await loadUsers()
  } catch (e) {
    const errors = e.response?.data?.errors
    userError.value = errors ? Object.values(errors).flat().join(', ') : e.response?.data?.message || 'Erreur'
  } finally { userLoading.value = false }
}
async function deleteUser(u) {
  if (!confirm(`Supprimer "${u.name}" ?`)) return
  try { await axios.delete(`/users/${u.id}`); await loadUsers() }
  catch (e) { alert(e.response?.data?.message || 'Erreur') }
}

onMounted(() => Promise.all([loadCategories(), loadSuppliers(), loadWebhooks(), loadUsers()]))
</script>
