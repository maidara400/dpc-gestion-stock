<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-800 to-green-950 px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-2xl mb-4">
          <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white">DPC Padel Stock</h1>
        <p class="text-green-300 mt-1 text-sm">Gestion de stock club de padel</p>
      </div>

      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Connexion</h2>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="label">Adresse email</label>
            <input
              v-model="form.email"
              type="email"
              class="input"
              placeholder="votre@email.com"
              required
              autocomplete="email"
            />
          </div>

          <div>
            <label class="label">Mot de passe</label>
            <input
              v-model="form.password"
              type="password"
              class="input"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
          </div>

          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ error }}
          </div>

          <button
            type="submit"
            class="btn-primary w-full justify-center py-3"
            :disabled="loading"
          >
            <span v-if="loading">Connexion en cours...</span>
            <span v-else>Se connecter</span>
          </button>
        </form>

        <!-- Connexion rapide -->
        <div class="mt-6 pt-5 border-t border-gray-100">
          <p class="text-xs text-gray-400 text-center mb-3 uppercase tracking-wide font-medium">Accès rapide</p>
          <div class="grid grid-cols-2 gap-2">
            <button
              @click="quickLogin('admin@padel.club', 'Admin@2024!')"
              :disabled="loading"
              class="group flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl border border-gray-200 hover:border-green-300 hover:bg-green-50 transition-all text-left disabled:opacity-50"
            >
              <div class="w-8 h-8 bg-green-100 text-green-700 rounded-lg flex items-center justify-center text-sm font-bold group-hover:bg-green-200 transition-colors">A</div>
              <div>
                <p class="text-xs font-semibold text-gray-700 text-center">Super Admin</p>
                <p class="text-xs text-gray-400 text-center">Accès complet</p>
              </div>
            </button>
            <button
              @click="quickLogin('caissier@padel.club', 'Caissier@2024!')"
              :disabled="loading"
              class="group flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all text-left disabled:opacity-50"
            >
              <div class="w-8 h-8 bg-blue-100 text-blue-700 rounded-lg flex items-center justify-center text-sm font-bold group-hover:bg-blue-200 transition-colors">C</div>
              <div>
                <p class="text-xs font-semibold text-gray-700 text-center">Caissier</p>
                <p class="text-xs text-gray-400 text-center">Caisse seulement</p>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''
  try {
    await authStore.login(form.value.email, form.value.password)
    router.push(authStore.isAdmin ? { name: 'dashboard' } : { name: 'caisse' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de connexion. Vérifiez vos identifiants.'
  } finally {
    loading.value = false
  }
}

async function quickLogin(email, password) {
  form.value = { email, password }
  await handleLogin()
}
</script>
