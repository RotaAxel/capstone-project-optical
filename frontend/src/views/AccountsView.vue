<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div><h2 class="text-base font-semibold text-gray-800">User Accounts</h2><p class="text-xs text-gray-500">Manage system users and roles</p></div>
      <button @click="openModal()" class="btn-primary">Add User</button>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div>
      </div>
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr><th class="table-th">Name</th><th class="table-th">Email</th><th class="table-th">Role</th><th class="table-th">Status</th><th class="table-th">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 transition-colors">
            <td class="table-td">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-xs font-bold shrink-0">{{ u.name?.charAt(0) }}</div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ u.name }}</p>
                  <p class="text-xs text-gray-400">{{ u.phone ?? '—' }}</p>
                </div>
              </div>
            </td>
            <td class="table-td text-sm">{{ u.email }}</td>
            <td class="table-td">
              <span :class="{
                'badge-blue':   u.role === 'admin',
                'badge-green':  u.role === 'receptionist',
                'badge-yellow': u.role === 'optometrist',
                'badge-orange': u.role === 'inventory_staff',
              }" class="capitalize">{{ u.role?.replace('_', ' ') }}</span>
            </td>
            <td class="table-td">
              <span :class="u.is_active ? 'badge-green' : 'badge-red'">{{ u.is_active ? 'Active' : 'Inactive' }}</span>
            </td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button @click="openModal(u)" class="btn-secondary btn-sm">Edit</button>
                <button @click="toggleActive(u)" :class="u.is_active ? 'btn-danger' : 'btn-success'" class="btn-sm">{{ u.is_active ? 'Deactivate' : 'Activate' }}</button>
              </div>
            </td>
          </tr>
          <tr v-if="!users.length"><td colspan="5" class="text-center py-12 text-gray-400 text-sm">No users found.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b"><h3 class="font-semibold">{{ editingId ? 'Edit User' : 'Add New User' }}</h3><button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button></div>
        <form @submit.prevent="save" class="px-6 py-5 space-y-4">
          <div><label class="label">Full Name *</label><input v-model="form.name" class="input" required /></div>
          <div><label class="label">Email *</label><input v-model="form.email" type="email" class="input" required /></div>
          <div><label class="label">Phone</label><input v-model="form.phone" class="input" /></div>
          <div>
            <label class="label">Role *</label>
            <select v-model="form.role" class="input" required>
              <option value="receptionist">Receptionist</option>
              <option value="optometrist">Optometrist</option>
              <option value="inventory_staff">Inventory Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div><label class="label">Password {{ editingId ? '(leave blank to keep)' : '*' }}</label><input v-model="form.password" type="password" class="input" :required="!editingId" /></div>
          <div v-if="form.password"><label class="label">Confirm Password</label><input v-model="form.password_confirmation" type="password" class="input" /></div>
          <div v-if="formError" class="text-sm text-red-600 bg-red-50 rounded px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save User' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const users      = ref([])
const loading    = ref(true)
const showModal  = ref(false)
const editingId  = ref(null)
const saving     = ref(false)
const formError  = ref('')

const emptyForm = () => ({ name: '', email: '', phone: '', role: 'receptionist', password: '', password_confirmation: '' })
const form = ref(emptyForm())

async function fetchUsers() {
  loading.value = true
  try { users.value = (await api.get('/users')).data.data ?? [] }
  catch { /* backend unavailable */ }
  finally { loading.value = false }
}

function openModal(user = null) {
  formError.value = ''
  if (user) { editingId.value = user.id; form.value = { ...user, password: '', password_confirmation: '' } }
  else { editingId.value = null; form.value = emptyForm() }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  formError.value = ''; saving.value = true
  try {
    if (editingId.value) await api.put(`/users/${editingId.value}`, form.value)
    else await api.post('/users', form.value)
    closeModal(); fetchUsers()
  } catch (e) {
    formError.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || 'Failed to save user.'
  } finally { saving.value = false }
}

async function toggleActive(user) {
  await api.put(`/users/${user.id}`, { is_active: !user.is_active })
  fetchUsers()
}

onMounted(fetchUsers)
</script>
