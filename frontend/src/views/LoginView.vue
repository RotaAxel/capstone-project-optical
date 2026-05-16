<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-600 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-blue-500 px-8 py-10 text-center">
          <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-4">
            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-white">Acebedo Optical Clinic</h1>
          <p class="text-blue-100 text-sm mt-1">Patient Records & Inventory System</p>
        </div>

        <!-- Form -->
        <div class="px-8 py-8">
          <h2 class="text-lg font-semibold text-gray-800 mb-6">Sign in to your account</h2>

          <form @submit.prevent="handleLogin" class="space-y-4">
            <div>
              <label class="label">Email address</label>
              <input v-model="form.email" type="email" placeholder="Enter your email" class="input" required autocomplete="email" />
            </div>

            <div>
              <label class="label">Password</label>
              <div class="relative">
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Enter your password" class="input pr-10" required autocomplete="current-password" />
                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="!showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                  </svg>
                </button>
              </div>
            </div>

            <div v-if="error" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
              {{ error }}
            </div>

            <button type="submit" class="btn-primary w-full py-2.5 mt-2" :disabled="loading">
              <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ loading ? 'Signing in...' : 'Sign In' }}
            </button>
          </form>

          <div class="mt-6 p-4 bg-gray-50 rounded-lg text-xs text-gray-500">
            <p class="font-medium text-gray-600 mb-1">Demo Accounts</p>
            <p>Admin: admin@acebedo.com</p>
            <p>Receptionist: reception@acebedo.com</p>
            <p>Optometrist: optometrist@acebedo.com</p>
            <p class="mt-1">Password: <span class="font-mono">password</span></p>
          </div>
        </div>
      </div>

      <p class="text-center text-blue-200 text-xs mt-6">&copy; 2026 Acebedo Optical Clinic. All rights reserved.</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

const form         = ref({ email: '', password: '' })
const loading      = ref(false)
const error        = ref('')
const showPassword = ref(false)

async function handleLogin() {
  error.value   = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'Invalid email or password.'
  } finally {
    loading.value = false
  }
}
</script>
