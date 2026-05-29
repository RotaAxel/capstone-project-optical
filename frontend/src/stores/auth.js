import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  // sessionStorage clears on tab/browser close — reduces XSS token theft window vs localStorage
  const token = ref(sessionStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin          = computed(() => user.value?.role === 'admin')
  const isReceptionist   = computed(() => user.value?.role === 'receptionist')
  const isOptometrist    = computed(() => user.value?.role === 'optometrist')
  const isInventoryStaff = computed(() => user.value?.role === 'inventory_staff')

  // Check if current user has any of the given roles
  function can(...roles) {
    return roles.includes(user.value?.role)
  }

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    user.value  = data.user
    token.value = data.token
    sessionStorage.setItem('token', data.token)
    localStorage.setItem('user',  JSON.stringify(data.user))
  }

  async function logout() {
    await api.post('/logout').catch(() => {})
    user.value  = null
    token.value = null
    sessionStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return { user, token, isAuthenticated, isAdmin, isReceptionist, isOptometrist, isInventoryStaff, can, login, logout }
})
